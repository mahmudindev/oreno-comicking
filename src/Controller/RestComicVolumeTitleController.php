<?php

namespace App\Controller;

use App\Entity\ComicVolumeTitle;
use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Repository\ComicVolumeRepository;
use App\Repository\ComicVolumeTitleRepository;
use App\Repository\LanguageRepository;
use App\Util\UrlQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute as Routing;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/comics/{comicCode}/volumes/{volumeNumber}/titles',
    name: 'rest_comic_volume_title_'
)]
class RestComicVolumeTitleController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicVolumeRepository $comicVolumeRepository,
        private readonly ComicVolumeTitleRepository $comicVolumeTitleRepository,
        private readonly LanguageRepository $languageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['volumeComicCodes'] = [$comicCode];
        $criteria['volumeNumbers'] = [$volumeNumber];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order) {
            \array_unshift($orderBy, new OrderByDto('ulid', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicVolumeTitleRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicVolumeTitleRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        foreach ($result as $v) {
            $aLastModified = $response->getLastModified();
            $bLastModified = $v->getUpdatedAt() ?? $v->getCreatedAt();
            if (!$aLastModified || $aLastModified < $bLastModified) {
                $response->setLastModified($bLastModified);
            }
        }
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('', name: 'post', methods: [Request::METHOD_POST])]
    public function post(
        Request $request,
        string $comicCode,
        string $volumeNumber
    ): Response {
        $parent = $this->comicVolumeRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'number' => $volumeNumber
        ]);
        if (!$parent) throw new BadRequestException('Comic Volume does not exists.');
        $result = new ComicVolumeTitle();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['content'])) $result->setContent($content['content']);
                if (isset($content['isSynonym'])) $result->setSynonym($content['isSynonym']);
                if (isset($content['isLatinized'])) $result->setLatinized($content['isLatinized']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $result->setVolume($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_volume_title_get', [
            'comicCode' => $result->getVolumeComicCode(),
            'volumeNumber' => $result->getVolumeNumber(),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{ulid}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        Ulid $ulid
    ): Response {
        $result = $this->comicVolumeTitleRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Volume Title not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{ulid}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        Ulid $ulid
    ): Response {
        $result = $this->comicVolumeTitleRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Volume Title not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['content'])) $result->setContent($content['content']);
                if (isset($content['isSynonym'])) $result->setSynonym($content['isSynonym']);
                if (isset($content['isLatinized'])) $result->setLatinized($content['isLatinized']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_volume_title_get', [
            'comicCode' => $result->getVolumeComicCode(),
            'volumeNumber' => $result->getVolumeNumber(),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{code}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $volumeNumber,
        Ulid $ulid
    ): Response {
        $result = $this->comicVolumeTitleRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Title not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

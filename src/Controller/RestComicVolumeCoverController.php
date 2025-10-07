<?php

namespace App\Controller;

use App\Entity\ComicVolumeCover;
use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Repository\ComicVolumeCoverRepository;
use App\Repository\ComicVolumeRepository;
use App\Repository\ImageRepository;
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
    path: '/api/rest/comics/{comicCode}/volumes/{volumeNumber}/covers',
    name: 'rest_comic_volume_cover_'
)]
class RestComicVolumeCoverController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicVolumeRepository $comicVolumeRepository,
        private readonly ComicVolumeCoverRepository $comicVolumeCoverRepository,
        private readonly ImageRepository $imageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string | null $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['volumeComicCodes'] = [$comicCode];
        $criteria['volumeNumbers'] = [$volumeNumber];
        $criteria['linkWebsiteHosts'] = $queries->all('linkWebsiteHost', 'linkWebsiteHosts');
        $criteria['linkRelativeReferences'] = $queries->all('linkRelativeReference', 'linkRelativeReferences');
        $criteria['linkHREFs'] = $queries->all('linkHREF', 'linkHREFs');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order) {
            \array_unshift($orderBy, new OrderByDto('ulid', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicVolumeCoverRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicVolumeCoverRepository->countCustom($criteria);
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
        $result = new ComicVolumeCover();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['imageULID'])) {
                    $r1 = $this->imageRepository->findOneBy([
                        'ulid' => $content['imageULID']
                    ]);
                    if (!$r1) throw new BadRequestException('Image does not exists.');
                    $result->setImage($r1);
                }
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
        $headers['Location'] = $this->generateUrl('rest_comic_volume_cover_get', [
            'comicCode' => $result->getVolumeComicCode(),
            'volumeNumber' => $result->getVolumeNumber(),
            'imageULID' => $result->getImageULID()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{imageULID}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        Ulid $imageULID
    ): Response {
        $result = $this->comicVolumeCoverRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'image' => $this->imageRepository->findOneBy(['ulid' => $imageULID])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Volume Cover not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{imageULID}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $volumeNumber,
        Ulid $imageULID
    ): Response {
        $result = $this->comicVolumeCoverRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'image' => $this->imageRepository->findOneBy(['ulid' => $imageULID])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Volume Cover not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['linkWebsiteHost'])) {
                    $r1 = $this->imageRepository->findOneBy([
                        'ulid' => $content['imageULID']
                    ]);
                    if (!$r1) throw new BadRequestException('Image does not exists.');
                    $result->setImage($r1);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_volume_cover_get', [
            'comicCode' => $result->getVolumeComicCode(),
            'volumeNumber' => $result->getVolumeNumber(),
            'imageULID' => $result->getImageULID()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{imageULID}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $volumeNumber,
        Ulid $imageULID
    ): Response {
        $result = $this->comicVolumeCoverRepository->findOneBy([
            'volume' => $this->comicVolumeRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $volumeNumber
            ]),
            'image' => $this->imageRepository->findOneBy(['ulid' => $imageULID])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Cover not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

namespace App\Controller;

use App\Entity\ComicChapter;
use App\Model\OrderByDto;
use App\Repository\ComicChapterRepository;
use App\Repository\ComicRepository;
use App\Repository\ComicVolumeRepository;
use App\Repository\LinkRepository;
use App\Repository\WebsiteRepository;
use App\Util\UrlQuery;
use App\Util\StringUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute as Routing;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/comics/{comicCode}/chapters',
    name: 'rest_comic_chapter_'
)]
class RestComicChapterController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicChapterRepository $comicChapterRepository,
        private readonly ComicVolumeRepository $comicVolumeRepository,
        private readonly LinkRepository $linkRepository,
        private readonly WebsiteRepository $websiteRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['comicCodes'] = [$comicCode];
        $criteria['volumeNumbers'] = $queries->all('volumeNumber', 'volumeNumbers');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('number', $order));
            \array_unshift($orderBy, new OrderByDto('version', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicChapterRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicChapterRepository->countCustom($criteria);
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
        string $comicCode
    ): Response {
        $parent = $this->comicRepository->findOneBy(['code' => $comicCode]);
        if (!$parent) throw new BadRequestException('Comic does not exists.');
        $result = new ComicChapter();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['number'])) $result->setNumber($content['number']);
                if (isset($content['version'])) $result->setVersion($content['version']);
                if (isset($content['releasedAt'])) {
                    $r1 = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, $content['releasedAt']);
                    if (!$r1) throw new BadRequestException('Released At could not be parsed.');
                    $result->setReleasedAt($r1);
                }
                if (isset($content['thumbnailLinkWebsiteHost'])) {
                    $r2 = $this->linkRepository->findOneBy([
                        'website' => $this->websiteRepository->findOneBy([
                            'host' => $content['thumbnailLinkWebsiteHost']
                        ]),
                        'relativeReference' => $content['thumbnailLinkRelativeReference'] ?? ''
                    ]);
                    if (!$r2) throw new BadRequestException('Thumbnail (Link) does not exists.');
                    $result->setThumbnailLink($r2);
                }
                if (isset($content['volumeNumber'])) {
                    $r3 = $this->comicVolumeRepository->findOneBy(['number' => $content['volumeNumber']]);
                    if (!$r3) throw new BadRequestException('Comic Volume does not exists.');
                    $result->setVolume($r3);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $result->setComic($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_chapter_get', [
            'comicCode' => $result->getComicCode(),
            'nv' => $result->getNumber() . StringUtil::prefix($result->getVersion() ?? '', '+')
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    public static function parseSlug(string $nv)
    {
        return \explode('+', $nv, 2);
    }

    #[Routing\Route('/{nv}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $nv
    ): Response {
        $pathParams = $this::parseSlug($nv);
        $result = $this->comicChapterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'number' => $pathParams[0],
            'version' => $pathParams[1] ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{nv}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $nv
    ): Response {
        $pathParams = $this::parseSlug($nv);
        $result = $this->comicChapterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'number' => $pathParams[0],
            'version' => $pathParams[1] ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['number'])) $result->setNumber($content['number']);
                if (isset($content['version'])) $result->setVersion($content['version']);
                if (isset($content['releasedAt'])) {
                    if ($content['releasedAt'] == null) {
                        $result->setReleasedAt(null);
                    } else {
                        $r1 = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, $content['releasedAt']);
                        if (!$r1) throw new BadRequestException('Released At could not be parsed.');
                        $result->setReleasedAt($r1);
                    }
                }
                if (isset($content['thumbnailLinkWebsiteHost'])) {
                    if ($content['thumbnailLinkWebsiteHost'] == null) {
                        $result->setThumbnail(null);
                    } else {
                        $r2 = $this->linkRepository->findOneBy([
                            'website' => $this->websiteRepository->findOneBy([
                                'host' => $content['thumbnailLinkWebsiteHost']
                            ]),
                            'relativeReference' => $content['thumbnailLinkRelativeReference'] ?? ''
                        ]);
                        if (!$r2) throw new BadRequestException('Thumbnail (Link) does not exists.');
                        $result->setThumbnailLink($r2);
                    }
                }
                if (isset($content['volumeNumber'])) {
                    if ($content['volumeNumber'] == null) {
                        $result->setVolume(null);
                    } else {
                        $r3 = $this->comicVolumeRepository->findOneBy(['number' => $content['volumeNumber']]);
                        if (!$r3) throw new BadRequestException('Comic Volume does not exists.');
                        $result->setVolume($r3);
                    }
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_chapter_get', [
            'comicCode' => $result->getComicCode(),
            'nv' => $result->getNumber() . StringUtil::prefix($result->getVersion() ?? '', '+')
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{nv}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $nv
    ): Response {
        $pathParams = $this::parseSlug($nv);
        $result = $this->comicChapterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'number' => $pathParams[0],
            'version' => $pathParams[1] ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

namespace App\Controller;

use App\Entity\ComicChapterTitle;
use App\Model\OrderByDto;
use App\Repository\ComicChapterRepository;
use App\Repository\ComicChapterTitleRepository;
use App\Repository\ComicRepository;
use App\Repository\LanguageRepository;
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
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/comics/{comicCode}/chapters/{chapterNV}/titles',
    name: 'rest_comic_chapter_title_'
)]
class RestComicChapterTitleController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicChapterRepository $comicChapterRepository,
        private readonly ComicChapterTitleRepository $comicChapterTitleRepository,
        private readonly LanguageRepository $languageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        string $chapterNV,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter]string | null $order = null
    ): Response {
        $pathParams = RestComicChapterController::parseSlug($chapterNV);
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['chapterComicCodes'] = [$comicCode];
        $criteria['chapterNumbers'] = [$pathParams[0]];
        $criteria['chapterVersions'] = [$pathParams[1] ?? ''];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('ulid', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicChapterTitleRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicChapterTitleRepository->countCustom($criteria);
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
        string $chapterNV
    ): Response {
        $pathParams = RestComicChapterController::parseSlug($chapterNV);
        $parent = $this->comicChapterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'number' => $pathParams[0],
            'version' => $pathParams[1] ?? ''
        ]);
        if (!$parent) throw new BadRequestException('Comic Chapter does not exists.');
        $result = new ComicChapterTitle();
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
        $result->setChapter($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_chapter_title_get', [
            'comicCode' => $result->getChapterComicCode(),
            'chapterNV' => $result->getChapterNumber() . StringUtil::prefix($result->getChapterVersion() ?? '', '+'),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{ulid}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $chapterNV,
        Ulid $ulid
    ): Response {
        $pathParams = RestComicChapterController::parseSlug($chapterNV);
        $result = $this->comicChapterTitleRepository->findOneBy([
            'chapter' => $this->comicChapterRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $pathParams[0],
                'version' => $pathParams[1] ?? ''
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter Title not found.');

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
        string $chapterNV,
        Ulid $ulid
    ): Response {
        $pathParams = RestComicChapterController::parseSlug($chapterNV);
        $result = $this->comicChapterTitleRepository->findOneBy([
            'chapter' => $this->comicChapterRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $pathParams[0],
                'version' => $pathParams[1] ?? ''
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter Title not found.');
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
        $headers['Location'] = $this->generateUrl('rest_comic_chapter_title_get', [
            'comicCode' => $result->getChapterComicCode(),
            'chapterNV' => $result->getChapterNumber() . StringUtil::prefix($result->getChapterVersion() ?? '', '+'),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{ulid}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $chapterNV,
        Ulid $ulid
    ): Response {
        $pathParams = RestComicChapterController::parseSlug($chapterNV);
        $result = $this->comicChapterTitleRepository->findOneBy([
            'chapter' => $this->comicChapterRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'number' => $pathParams[0],
                'version' => $pathParams[1] ?? ''
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Chapter Title not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

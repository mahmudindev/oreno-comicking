<?php

namespace App\Controller;

use App\Entity\ComicAuthorNote;
use App\Model\OrderByDto;
use App\Repository\ComicAuthorKindRepository;
use App\Repository\ComicAuthorNoteRepository;
use App\Repository\ComicAuthorRepository;
use App\Repository\ComicRepository;
use App\Repository\PersonRepository;
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
    path: '/api/rest/comics/{comicCode}/authors/{authorTypeCode}:{authorPersonCode}/notes',
    name: 'rest_comic_author_note_'
)]
class RestComicAuthorNoteController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicAuthorRepository $comicAuthorRepository,
        private readonly ComicAuthorKindRepository $comicAuthorKindRepository,
        private readonly ComicAuthorNoteRepository $comicAuthorNoteRepository,
        private readonly PersonRepository $personRepository,
        private readonly LanguageRepository $languageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        string $authorTypeCode,
        string $authorPersonCode,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['authorComicCodes'] = [$comicCode];
        $criteria['authorTypeCodes'] = [$authorTypeCode];
        $criteria['authorPersonCodes'] = [$authorPersonCode];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('ulid', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicAuthorNoteRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicAuthorNoteRepository->countCustom($criteria);
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
        string $authorTypeCode,
        string $authorPersonCode
    ): Response {
        $parent = $this->comicAuthorRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'type' => $this->comicAuthorKindRepository->findOneBy(['code' => $authorTypeCode]),
            'person' => $this->personRepository->findOneBy(['code' => $authorPersonCode])
        ]);
        if (!$parent) throw new BadRequestException('Comic Author does not exists.');
        $result = new ComicAuthorNote();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['content'])) $result->setContent($content['content']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $result->setAuthor($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_author_note_get', [
            'comicCode' => $result->getAuthorComicCode(),
            'authorTypeCode' => $result->getAuthorTypeCode(),
            'authorPersonCode' => $result->getAuthorPersonCode(),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{ulid}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $authorTypeCode,
        string $authorPersonCode,
        Ulid $ulid
    ): Response {
        $result = $this->comicAuthorNoteRepository->findOneBy([
            'author' => $this->comicAuthorRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'type' => $this->comicAuthorKindRepository->findOneBy(['code' => $authorTypeCode]),
                'person' => $this->personRepository->findOneBy(['code' => $authorPersonCode])
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Author Note not found.');

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
        string $authorTypeCode,
        string $authorPersonCode,
        Ulid $ulid
    ): Response {
        $result = $this->comicAuthorNoteRepository->findOneBy([
            'author' => $this->comicAuthorRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'type' => $this->comicAuthorKindRepository->findOneBy(['code' => $authorTypeCode]),
                'person' => $this->personRepository->findOneBy(['code' => $authorPersonCode])
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Author Note not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['content'])) $result->setContent($content['content']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_author_note_get', [
            'comicCode' => $result->getAuthorComicCode(),
            'authorTypeCode' => $result->getAuthorTypeCode(),
            'authorPersonCode' => $result->getAuthorPersonCode(),
            'ulid' => $result->getUlid()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{ulid}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $authorTypeCode,
        string $authorPersonCode,
        Ulid $ulid
    ): Response {
        $result = $this->comicAuthorNoteRepository->findOneBy([
            'author' => $this->comicAuthorRepository->findOneBy([
                'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
                'type' => $this->comicAuthorKindRepository->findOneBy(['code' => $authorTypeCode]),
                'person' => $this->personRepository->findOneBy(['code' => $authorPersonCode])
            ]),
            'ulid' => $ulid
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Author Note not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

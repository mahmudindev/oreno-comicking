<?php

namespace App\Controller;

use App\Entity\ComicCharacter;
use App\Model\OrderByDto;
use App\Repository\ComicCharacterRepository;
use App\Repository\ComicRepository;
use App\Repository\CharacterRepository;
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
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/comics/{comicCode}/characters',
    name: 'rest_comic_character_'
)]
class RestComicCharacterController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicCharacterRepository $comicCharacterRepository,
        private readonly CharacterRepository $characterRepository
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
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('characterCode', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicCharacterRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicCharacterRepository->countCustom($criteria);
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
        $result = new ComicCharacter();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['characterCode'])) {
                    $r1 = $this->characterRepository->findOneBy(['code' => $content['characterCode']]);
                    if (!$r1) throw new BadRequestException('Character does not exists.');
                    $result->setCharacter($r1);
                }
                if (isset($content['isMain'])) $result->setMain($content['isMain']);
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
        $headers['Location'] = $this->generateUrl('rest_comic_character_get', [
            'comicCode' => $result->getComicCode(),
            'characterCode' => $result->getCharacterCode()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{characterCode}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $characterCode
    ): Response {
        $result = $this->comicCharacterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'character' => $this->characterRepository->findOneBy(['code' => $characterCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Character not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{characterCode}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $characterCode
    ): Response {
        $result = $this->comicCharacterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'character' => $this->characterRepository->findOneBy(['code' => $characterCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Character not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['characterCode'])) {
                    $r1 = $this->characterRepository->findOneBy(['code' => $content['characterCode']]);
                    if (!$r1) throw new BadRequestException('Character does not exists.');
                    $result->setCharacter($r1);
                }
                if (isset($content['isMain'])) $result->setMain($content['isMain']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_character_get', [
            'comicCode' => $result->getComicCode(),
            'characterCode' => $result->getCharacterCode()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{characterCode}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $characterCode
    ): Response {
        $result = $this->comicCharacterRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'character' => $this->characterRepository->findOneBy(['code' => $characterCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Character not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

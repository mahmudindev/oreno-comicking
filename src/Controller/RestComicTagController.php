<?php

namespace App\Controller;

use App\Entity\ComicTag;
use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Repository\ComicTagRepository;
use App\Repository\TagRepository;
use App\Repository\TagKindRepository;
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
    path: '/api/rest/comics/{comicCode}/tags',
    name: 'rest_comic_tag_'
)]
class RestComicTagController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicTagRepository $comicTagRepository,
        private readonly TagRepository $tagRepository,
        private readonly TagKindRepository $tagKindRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $comicCode,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string | null $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['comicCodes'] = [$comicCode];
        $criteria['tagTypeCodes'] = $queries->all('tagTypeCode', 'tagTypeCodes');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('tagCode', $order));
            \array_unshift($orderBy, new OrderByDto('tagTypeCode', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicTagRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicTagRepository->countCustom($criteria);
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
        $result = new ComicTag();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['tagTypeCode']) && isset($content['tagCode'])) {
                    $r1 = $this->tagRepository->findOneBy([
                        'type' => $this->tagKindRepository->findOneBy(['code' => $content['tagTypeCode']]),
                        'code' => $content['tagCode']
                    ]);
                    if (!$r1) throw new BadRequestException('Tag does not exists.');
                    $result->setTag($r1);
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
        $headers['Location'] = $this->generateUrl('rest_comic_tag_get', [
            'comicCode' => $result->getComicCode(),
            'typeCode' => $result->getTagTypeCode(),
            'code' => $result->getTagCode()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->comicTagRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'tag' => $this->tagRepository->findOneBy([
                'type' => $this->tagKindRepository->findOneBy(['code' => $typeCode]),
                'code' => $code
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Tag not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->comicTagRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'tag' => $this->tagRepository->findOneBy([
                'type' => $this->tagKindRepository->findOneBy(['code' => $typeCode]),
                'code' => $code
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Tag not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['tagTypeCode']) && isset($content['tagCode'])) {
                    $r1 = $this->tagRepository->findOneBy([
                        'type' => $this->tagKindRepository->findOneBy(['code' => $content['tagTypeCode']]),
                        'code' => $content['tagCode']
                    ]);
                    if (!$r1) throw new BadRequestException('Tag does not exists.');
                    $result->setTag($r1);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_tag_get', [
            'comicCode' => $result->getComicCode(),
            'typeCode' => $result->getTagTypeCode(),
            'code' => $result->getTagCode()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->comicTagRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'tag' => $this->tagRepository->findOneBy([
                'type' => $this->tagKindRepository->findOneBy(['code' => $typeCode]),
                'code' => $code
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Tag not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

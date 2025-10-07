<?php

namespace App\Controller;

use App\Entity\ComicRelation;
use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Repository\ComicRelationKindRepository;
use App\Repository\ComicRelationRepository;
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
    path: '/api/rest/comics/{parentCode}/relations',
    name: 'rest_comic_relation_'
)]
class RestComicRelationController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicRelationRepository $comicRelationRepository,
        private readonly ComicRelationKindRepository $comicRelationKindRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $parentCode,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string | null $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['parentCodes'] = [$parentCode];
        $criteria['typeCodes'] = $queries->all('typeCode', 'typeCodes');
        $criteria['childCodes'] = $queries->all('childCode', 'childCodes');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('childCode', $order));
            \array_unshift($orderBy, new OrderByDto('typeCode', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicRelationRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicRelationRepository->countCustom($criteria);
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
        string $parentCode
    ): Response {
        $parent = $this->comicRepository->findOneBy(['code' => $parentCode]);
        if (!$parent) throw new BadRequestException('Comic parent does not exists.');
        $result = new ComicRelation();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->comicRelationKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Comic Relation Type does not exists.');
                    $result->setType($r1);
                }
                if (isset($content['childCode'])) {
                    $r2 = $this->comicRepository->findOneBy(['code' => $content['childCode']]);
                    if (!$r2) throw new BadRequestException('Comic child does not exists.');
                    $result->setChild($r2);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $result->setParent($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_relation_get', [
            'parentCode' => $result->getParentCode(),
            'typeCode' => $result->getTypeCode(),
            'childCode' => $result->getChildCode()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{typeCode}:{childCode}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $parentCode,
        string $typeCode,
        string $childCode
    ): Response {
        $result = $this->comicRelationRepository->findOneBy([
            'parent' => $this->comicRepository->findOneBy(['code' => $parentCode]),
            'type' => $this->comicRelationKindRepository->findOneBy(['code' => $typeCode]),
            'child' => $this->comicRepository->findOneBy(['code' => $childCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Relation not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{typeCode}:{childCode}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $parentCode,
        string $typeCode,
        string $childCode
    ): Response {
        $result = $this->comicRelationRepository->findOneBy([
            'parent' => $this->comicRepository->findOneBy(['code' => $parentCode]),
            'type' => $this->comicRelationKindRepository->findOneBy(['code' => $typeCode]),
            'child' => $this->comicRepository->findOneBy(['code' => $childCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Relation not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->comicRelationKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Comic Relation Type does not exists.');
                    $result->setType($r1);
                }
                if (isset($content['childCode'])) {
                    $r2 = $this->comicRepository->findOneBy(['code' => $content['childCode']]);
                    if (!$r2) throw new BadRequestException('Comic child does not exists.');
                    $result->setChild($r2);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_relation_get', [
            'parentCode' => $result->getParentCode(),
            'typeCode' => $result->getTypeCode(),
            'childCode' => $result->getChildCode()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{typeCode}:{childCode}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $parentCode,
        string $typeCode,
        string $childCode
    ): Response {
        $result = $this->comicRelationRepository->findOneBy([
            'parent' => $this->comicRepository->findOneBy(['code' => $parentCode]),
            'type' => $this->comicRelationKindRepository->findOneBy(['code' => $typeCode]),
            'child' => $this->comicRepository->findOneBy(['code' => $childCode])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic Relation not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

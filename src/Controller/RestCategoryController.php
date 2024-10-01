<?php

namespace App\Controller;

use App\Entity\Category;
use App\Model\OrderByDto;
use App\Repository\CategoryKindRepository;
use App\Repository\CategoryRepository;
use App\Repository\LinkRepository;
use App\Repository\WebsiteRepository;
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
    path: '/api/rest/categories',
    name: 'rest_category_'
)]
class RestCategoryController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryKindRepository $categoryKindRepository,
        private readonly LinkRepository $linkRepository,
        private readonly WebsiteRepository $websiteRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['typeCodes'] = $queries->all('typeCode', 'typeCodes');
        $criteria['parentCodes'] = $queries->all('parentCode', 'parentCodes');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('code', $order));
            \array_unshift($orderBy, new OrderByDto('typeCode', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->categoryRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->categoryRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['category']]);

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
        Request $request
    ): Response {
        $result = new Category();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->categoryKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Category Type does not exists.');
                    $result->setType($r1);
                }
                if (isset($content['code'])) $result->setCode($content['code']);
                if (isset($content['name'])) $result->setName($content['name']);
                if (isset($content['linkWebsiteHost'])) {
                    $r2 = $this->linkRepository->findOneBy([
                        'website' => $this->websiteRepository->findOneBy([
                            'host' => $content['linkWebsiteHost']
                        ]),
                        'relativeReference' => $content['linkRelativeReference'] ?? ''
                    ]);
                    if (!$r2) throw new BadRequestException('Link does not exists.');
                    $result->setLink($r2);
                }
                if (isset($content['parentCode'])) {
                    $r3 = $this->categoryRepository->findOneBy([
                        'type' => $r1,
                        'code' => $content['parentCode']
                    ]);
                    if (!$r3) throw new BadRequestException('Category parent does not exists.');
                    $result->setParent($r3);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_category_get', [
            'typeCode' => $result->getTypeCode(),
            'code' => $result->getCode()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['category']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->categoryRepository->findOneBy([
            'type' => $this->categoryKindRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Category not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['category']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->categoryRepository->findOneBy([
            'type' => $this->categoryKindRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Category not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->categoryKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Category Type does not exists.');
                    $result->setType($r1);
                }
                if (isset($content['code'])) $result->setCode($content['code']);
                if (isset($content['name'])) $result->setName($content['name']);
                if (isset($content['linkWebsiteHost'])) {
                    if ($content['linkWebsiteHost'] == null) {
                        $result->setLink(null);
                    } else {
                        $r2 = $this->linkRepository->findOneBy([
                            'website' => $this->websiteRepository->findOneBy([
                                'host' => $content['linkWebsiteHost']
                            ]),
                            'relativeReference' => $content['linkRelativeReference'] ?? ''
                        ]);
                        if (!$r2) throw new BadRequestException('Link does not exists.');
                        $result->setLink($r2);
                    }
                }
                if (isset($content['parentCode'])) {
                    if ($content['parentCode'] == null) {
                        $result->setParent(null);
                    } else {
                        $r3 = $this->categoryRepository->findOneBy([
                            'type' => isset($content['typeCode']) ? $r1 : $result->getType(),
                            'code' => $content['parentCode']
                        ]);
                        if (!$r3) throw new BadRequestException('Category parent does not exists.');
                        $result->setParent($r3);
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
        $headers['Location'] = $this->generateUrl('rest_category_get', [
            'typeCode' => $result->getTypeCode(),
            'code' => $result->getCode()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['category']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $typeCode,
        string $code
    ): Response {
        $result = $this->categoryRepository->findOneBy([
            'type' => $this->categoryKindRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Category not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

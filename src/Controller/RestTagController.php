<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Model\OrderByDto;
use App\Repository\LinkRepository;
use App\Repository\TagKindRepository;
use App\Repository\TagRepository;
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
    path: '/api/rest/tags',
    name: 'rest_tag_'
)]
class RestTagController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
        private readonly TagKindRepository $tagKindRepository,
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
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('code', $order));
            \array_unshift($orderBy, new OrderByDto('typeCode', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->tagRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->tagRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['tag']]);

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
        $result = new Tag();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->tagKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Tag Type does not exists.');
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
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_tag_get', [
            'typeCode' => $result->getTypeCode(),
            'code' => $result->getCode()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['tag']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $typeCode,
        string $code
    ): Response {
        $result = $this->tagRepository->findOneBy([
            'type' => $this->tagRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Tag not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['tag']]);

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
        $result = $this->tagRepository->findOneBy([
            'type' => $this->tagKindRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Tag not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['typeCode'])) {
                    $r1 = $this->tagKindRepository->findOneBy(['code' => $content['typeCode']]);
                    if (!$r1) throw new BadRequestException('Tag Type does not exists.');
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
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_tag_get', [
            'typeCode' => $result->getTypeCode(),
            'code' => $result->getCode()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['tag']]);
    }

    #[Routing\Route('/{typeCode}:{code}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $typeCode,
        string $code
    ): Response {
        $result = $this->tagRepository->findOneBy([
            'type' => $this->tagKindRepository->findOneBy(['code' => $typeCode]),
            'code' => $code
        ]);
        if (!$result) throw new NotFoundHttpException('Tag not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

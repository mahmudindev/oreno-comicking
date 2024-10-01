<?php

namespace App\Controller;

use App\Entity\Link;
use App\Model\OrderByDto;
use App\Repository\LinkRepository;
use App\Repository\WebsiteRepository;
use App\Util\UrlQuery;
use App\Util\Href;
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
    path: '/api/rest/links',
    name: 'rest_link_'
)]
class RestLinkController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
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
        $criteria['websiteHosts'] = $queries->all('websiteHost', 'websiteHosts');
        $criteria['relativeReferences'] = $queries->all('relativeReference', 'relativeReferences');
        $criteria['hrefs'] = $queries->all('href', 'hrefs');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('relativeReference', $order));
            \array_unshift($orderBy, new OrderByDto('websiteHost', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->linkRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->linkRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['link']]);

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
        $result = new Link();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['websiteHost'])) {
                    $r1 = $this->websiteRepository->findOneBy(['host' => $content['websiteHost']]);
                    if (!$r1) throw new BadRequestException('Website does not exists.');
                    $result->setWebsite($r1);
                }
                if (isset($content['relativeReference'])) $result->setRelativeReference($content['relativeReference']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_link_get', [
            'href' => \rawurlencode($result->getWebsiteHost() . $result->getRelativeReference())
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['link']]);
    }

    #[Routing\Route('/{href}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $href
    ): Response {
        $pathParams = new Href($href);
        $result = $this->linkRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
            'relativeReference' => $pathParams->getRelativeReference() ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Link not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['link']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{href}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $href
    ): Response {
        $pathParams = new Href($href);
        $result = $this->linkRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
            'relativeReference' => $pathParams->getRelativeReference() ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Link not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['websiteHost'])) {
                    $r1 = $this->websiteRepository->findOneBy(['host' => $content['websiteHost']]);
                    if (!$r1) throw new BadRequestException('Website does not exists.');
                    $result->setWebsite($r1);
                }
                if (isset($content['relativeReference'])) $result->setRelativeReference($content['relativeReference']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_link_get', [
            'href' => \rawurlencode($result->getWebsiteHost() . $result->getRelativeReference())
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['link']]);
    }

    #[Routing\Route('/{href}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $href
    ): Response {
        $pathParams = new Href($href);
        $result = $this->linkRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
            'relativeReference' => $pathParams->getRelativeReference() ?? ''
        ]);
        if (!$result) throw new NotFoundHttpException('Link not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

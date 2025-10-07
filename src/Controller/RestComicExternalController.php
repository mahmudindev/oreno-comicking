<?php

namespace App\Controller;

use App\Entity\ComicExternal;
use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Repository\ComicExternalRepository;
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
    path: '/api/rest/comics/{comicCode}/externals',
    name: 'rest_comic_external_'
)]
class RestComicExternalController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRepository $comicRepository,
        private readonly ComicExternalRepository $comicExternalRepository,
        private readonly LinkRepository $linkRepository,
        private readonly WebsiteRepository $websiteRepository
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
        $criteria['linkWebsiteHosts'] = $queries->all('linkWebsiteHost', 'linkWebsiteHosts');
        $criteria['linkRelativeReferences'] = $queries->all('linkRelativeReference', 'linkRelativeReferences');
        $criteria['linkHREFs'] = $queries->all('linkHREF', 'linkHREFs');
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order) {
            \array_unshift($orderBy, new OrderByDto('linkWebsiteName', $order));
            \array_unshift($orderBy, new OrderByDto('linkWebsiteHost', $order));
            \array_unshift($orderBy, new OrderByDto('linkRelativeReference', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicExternalRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicExternalRepository->countCustom($criteria);
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
        $result = new ComicExternal();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['linkWebsiteHost'])) {
                    $r1 = $this->linkRepository->findOneBy([
                        'website' => $this->websiteRepository->findOneBy([
                            'host' => $content['linkWebsiteHost']
                        ]),
                        'relativeReference' => $content['linkRelativeReference'] ?? '/'
                    ]);
                    if (!$r1) throw new BadRequestException('Link does not exists.');
                    $result->setLink($r1);
                }
                if (isset($content['isOfficial'])) $result->setOfficial($content['isOfficial']);
                if (isset($content['isCommunity'])) $result->setCommunity($content['isCommunity']);
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
        $headers['Location'] = $this->generateUrl('rest_comic_external_get', [
            'comicCode' => $result->getComicCode(),
            'linkHREF' => \rawurlencode($result->getLinkWebsiteHost() . $result->getLinkRelativeReference())
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{linkHREF}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $comicCode,
        string $linkHREF
    ): Response {
        $pathParams = new Href($linkHREF);
        $result = $this->comicExternalRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
                'relativeReference' => $pathParams->getRelativeReference() ?? '/'
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic External not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comic']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{linkHREF}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $comicCode,
        string $linkHREF
    ): Response {
        $pathParams = new Href($linkHREF);
        $result = $this->comicExternalRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
                'relativeReference' => $pathParams->getRelativeReference() ?? '/'
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic External not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['linkWebsiteHost'])) {
                    $r1 = $this->linkRepository->findOneBy([
                        'website' => $this->websiteRepository->findOneBy([
                            'host' => $content['linkWebsiteHost']
                        ]),
                        'relativeReference' => $content['linkRelativeReference'] ?? '/'
                    ]);
                    if (!$r1) throw new BadRequestException('Link does not exists.');
                    $result->setLink($r1);
                }
                if (isset($content['isOfficial'])) $result->setOfficial($content['isOfficial']);
                if (isset($content['isCommunity'])) $result->setCommunity($content['isCommunity']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comic_external_get', [
            'comicCode' => $result->getComicCode(),
            'linkHREF' => \rawurlencode($result->getLinkWebsiteHost() . $result->getLinkRelativeReference())
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comic']]);
    }

    #[Routing\Route('/{linkHREF}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $comicCode,
        string $linkHREF
    ): Response {
        $pathParams = new Href($linkHREF);
        $result = $this->comicExternalRepository->findOneBy([
            'comic' => $this->comicRepository->findOneBy(['code' => $comicCode]),
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams->getHost()]),
                'relativeReference' => $pathParams->getRelativeReference() ?? '/'
            ])
        ]);
        if (!$result) throw new NotFoundHttpException('Comic External not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

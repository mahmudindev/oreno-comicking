<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\Attribute as EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailedException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\Exception\ValidationFailedException as ValidatorValidationFailedException;

class HttpListener
{
    public function __construct(
        private readonly HttpKernelInterface $httpKernel
    ) {}

    #[EventDispatcher\AsEventListener(event: KernelEvents::REQUEST, priority: 4096)]
    public function onKernelRequestEarly(RequestEvent $event): void
    {
        $request = $event->getRequest();

        #
        # URI_RAWURLENCODE
        # - A complicated way to avoid double encode path parameters :)
        #
        if (!$request->attributes->get('_app_uri_rawurlencode') ?? true) {
            if (!$request->server->has('REQUEST_URI')) {
                return;
            }

            $oldPathInfo = $request->getPathInfo();

            if (\preg_match('/^\/(_|assets\/?)/', $oldPathInfo)) {
                return;
            }


            $newPathInfo = \join('/', \array_map(function (string $path) {
                return \rawurlencode($path);
            }, \explode('/', $oldPathInfo)));

            if ($oldPathInfo == $newPathInfo) {
                return;
            }

            $request->server->set('REQUEST_URI', $newPathInfo);
            $request->attributes->set('_app_uri_rawurlencode', true);

            $event->setResponse($this->httpKernel->handle(
                $request->duplicate(server: $request->server->all()),
                HttpKernelInterface::SUB_REQUEST
            ));
        }
    }

    #[EventDispatcher\AsEventListener(event: KernelEvents::REQUEST, priority: 30)]
    public function onKernelRequestAfterRouter(RequestEvent $event): void
    {
        $request = $event->getRequest();

        #
        # URI_RAWURLENCODE
        #
        if ($request->attributes->get('_app_uri_rawurlencode') ?? false) {
            foreach ($request->attributes->all('_route_params') as $key => $val) {
                $request->attributes->set($key, \rawurldecode($val));
            }
        }
    }

    #[EventDispatcher\AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (\str_starts_with($request->getPathInfo(), '/api/rest')) {
            $exception = $event->getThrowable();

            $resultStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $result = [
                'message' => 'The server encountered an internal error.'
            ];
            $resultHeaders = [];

            if ($exception instanceof HttpExceptionInterface) {
                $resultStatus = $exception->getStatusCode();
                $result['message'] = $exception->getMessage();
                $resultHeaders = $exception->getHeaders();

                if ($exception instanceof UnsupportedMediaTypeHttpException) {
                    if ($result['message'] == '') {
                        $result['message'] = 'The provided content-type is not supported.';
                    }
                }

                $previousException = $exception->getPrevious();

                if ($previousException instanceof PartialDenormalizationException) {
                    $result['message'] = '';

                    foreach ($previousException->getErrors() as $k => $v) {
                        if ($k > 0) {
                            $result['message'] .= ' ';
                        }

                        $result['message'] .= 'Property ' . $v->getPath() . ' value should be of type';
                        $result['message'] .= ' ' . implode('|', $v->getExpectedTypes() ?? ['?']) . '.';
                        $result['message'] .= ' ' . $v->getMessage();
                    }
                } else if (
                    $previousException instanceof ValidatorValidationFailedException ||
                    $previousException instanceof MessengerValidationFailedException
                ) {
                    $result['message'] = '';

                    foreach ($previousException->getViolations() as $k => $v) {
                        if ($k > 0) {
                            $result['message'] .= ' ';
                        }

                        $result['message'] .= 'Property ' . $v->getPropertyPath() . ' validation failed.';
                        $result['message'] .= ' ' . $v->getMessage();
                    }
                } else if ($previousException instanceof UniqueConstraintViolationException) {
                    $result['message'] = 'Duplicate resource not allowed.';
                }
            }

            $event->setResponse(new JsonResponse($result, $resultStatus, $resultHeaders));
        }
    }
}

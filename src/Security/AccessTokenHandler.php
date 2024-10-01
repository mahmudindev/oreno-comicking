<?php

namespace App\Security;

use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $httpRequestFactory,
        private string $issuer,
        private null|string|array $audience = null,
        private ?string $jwksURI = null,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $jwksURI = $this->jwksURI ?? $this->issuer . '.well-known/jwks.json';

        try {
            $jwtPayload = JWT::decode(
                $accessToken,
                new CachedKeySet(
                    $jwksURI,
                    $this->httpClient,
                    $this->httpRequestFactory,
                    $this->cacheItemPool,
                    null,
                    true
                )
            );

            if ((((array)$jwtPayload)['iss'] ?? '') != $this->issuer) {
                throw new AuthenticationException('Invalid access token issuer');
            }
            if ($this->audience) {
                $audience = ((array)$jwtPayload)['aud'] ?? '';

                if (\is_array($this->audience)) {
                    $validAudience = \in_array($audience, $this->audience);
                } else {
                    $validAudience = $audience == $this->audience;
                }

                if (!$validAudience) throw new AuthenticationException('Invalid access token audience');
            }
        } catch (\Exception $e) {
            $this->logger?->error('An error occurred while decoding and validating the token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new BadCredentialsException('Invalid credentials', $e->getCode(), $e);
        }

        return new UserBadge(\json_encode($jwtPayload, JSON_THROW_ON_ERROR));
    }
}

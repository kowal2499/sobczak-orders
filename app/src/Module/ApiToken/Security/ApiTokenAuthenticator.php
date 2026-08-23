<?php

namespace App\Module\ApiToken\Security;

use App\Module\ApiToken\Entity\ApiToken;
use App\Module\ApiToken\Repository\ApiTokenRepository;
use App\Module\ApiToken\Service\ApiTokenIssuer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Celowo nie implementuje AuthenticationEntryPointInterface: punktem wejścia firewalla zostaje
 * formularz logowania, więc żądanie bez poświadczeń dalej dostaje przekierowanie na /login.
 */
class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private const HEADER = 'Authorization';
    private const SCHEME = 'Bearer ';

    public function __construct(
        private readonly ApiTokenRepository $repository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with((string) $request->headers->get(self::HEADER), self::SCHEME);
    }

    public function authenticate(Request $request): Passport
    {
        $plainToken = substr((string) $request->headers->get(self::HEADER), strlen(self::SCHEME));

        if ('' === $plainToken) {
            throw new CustomUserMessageAuthenticationException('Brak tokenu w nagłówku Authorization.');
        }

        $apiToken = $this->repository->findOneByHash(ApiTokenIssuer::hash($plainToken));

        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('Nieznany token API.');
        }

        if ($apiToken->isExpired()) {
            throw new CustomUserMessageAuthenticationException('Token API wygasł.');
        }

        $user = $apiToken->getUser();

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Konto powiązane z tokenem jest nieaktywne.');
        }

        $this->refreshLastUsedAt($apiToken);

        // Identyfikatorem jest e-mail, nie token: ta wartość trafia do logów i do profilera.
        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), static fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function refreshLastUsedAt(ApiToken $apiToken): void
    {
        if (!$apiToken->shouldRefreshLastUsedAt()) {
            return;
        }

        $apiToken->markUsed();
        $this->repository->save($apiToken);
    }
}

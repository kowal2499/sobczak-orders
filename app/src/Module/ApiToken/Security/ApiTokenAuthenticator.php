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

    // RFC 7235 dopuszcza dowolną liczbę spacji po nazwie schematu i czyni ją nierozróżnialną
    // wielkością liter.
    private const SCHEME_PATTERN = '/^Bearer\b\s*(.*)$/i';

    public function __construct(
        private readonly ApiTokenRepository $repository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return null !== $this->extractToken($request);
    }

    public function authenticate(Request $request): Passport
    {
        $plainToken = $this->extractToken($request);

        if (null === $plainToken || '' === $plainToken) {
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

    private function extractToken(Request $request): ?string
    {
        $header = (string) $request->headers->get(self::HEADER);

        if (1 !== preg_match(self::SCHEME_PATTERN, $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
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

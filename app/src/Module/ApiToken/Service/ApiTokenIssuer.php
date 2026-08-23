<?php

namespace App\Module\ApiToken\Service;

use App\Entity\User;
use App\Module\ApiToken\DTO\IssuedApiTokenDTO;
use App\Module\ApiToken\Entity\ApiToken;
use App\Module\ApiToken\Repository\ApiTokenRepository;

class ApiTokenIssuer
{
    public const TOKEN_PREFIX = 'sob_';
    public const DEFAULT_TTL_DAYS = 90;

    private const RANDOM_BYTES = 32;

    public function __construct(
        private readonly ApiTokenRepository $repository,
    ) {
    }

    /**
     * @param int|null $ttlDays null oznacza token bezterminowy
     */
    public function issue(User $user, string $name, ?int $ttlDays = self::DEFAULT_TTL_DAYS): IssuedApiTokenDTO
    {
        if (null !== $ttlDays && $ttlDays < 1) {
            throw new \InvalidArgumentException('Ważność tokenu musi wynosić co najmniej jeden dzień.');
        }

        $plainToken = self::TOKEN_PREFIX . bin2hex(random_bytes(self::RANDOM_BYTES));

        $token = new ApiToken(
            $user,
            $name,
            self::hash($plainToken),
            null === $ttlDays ? null : new \DateTimeImmutable(sprintf('+%d days', $ttlDays)),
        );

        $this->repository->save($token);

        return new IssuedApiTokenDTO($token, $plainToken);
    }

    /**
     * SHA-256, nie bcrypt: token ma pełną entropię losową, więc rozciąganie klucza nic nie wnosi,
     * a wyszukanie musi być pojedynczym SELECT po zaindeksowanej kolumnie.
     */
    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}

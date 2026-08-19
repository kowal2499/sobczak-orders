<?php

namespace App\Module\BonusSettlement\Service;

use App\Entity\User;
use App\Module\Authorization\Service\GrantsResolver;
use App\Module\BonusSettlement\DTO\DepartmentMembershipDTO;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\DepartmentGrantMap;
use App\Repository\UserRepository;

/**
 * Odpowiada na pytanie "komu przysługuje premia z którego działu". Grant działowy jest jedynym
 * kryterium - użytkownik bez żadnego z sześciu grantów nie trafia do rozliczenia w ogóle.
 *
 * Nie ma zapytania odwrotnego "kto ma grant X", bo granty rozwiązuje się per użytkownik
 * (z cache). Przy kilkudziesięciu pracownikach iteracja wystarcza.
 */
class DepartmentMembershipProvider
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GrantsResolver $grantsResolver,
    ) {
    }

    /**
     * @return DepartmentMembershipDTO[]
     */
    public function all(): array
    {
        $memberships = [];
        foreach ($this->userRepository->findBy(['active' => true], ['id' => 'ASC']) as $user) {
            array_push($memberships, ...$this->forUser($user));
        }

        return $memberships;
    }

    /**
     * @return DepartmentMembershipDTO[]
     */
    public function forUser(User $user): array
    {
        // Świadomie getGrants(), nie isGranted(): to drugie zwraca true na wszystko dla
        // authorization.admin i przyznałoby administratorowi komplet sześciu działów.
        $grants = $this->grantsResolver->getGrants($user);
        $label = $this->labelFor($user);

        $memberships = [];
        foreach (DepartmentGrantMap::all() as $slug => $grant) {
            if (!in_array($grant, $grants, true)) {
                continue;
            }
            $memberships[] = new DepartmentMembershipDTO(
                $user,
                $label,
                $slug,
                DepartmentEnum::from($slug)->getName(),
            );
        }

        return $memberships;
    }

    private function labelFor(User $user): string
    {
        $fullName = trim($user->getUserFullName());

        return '' !== $fullName ? $fullName : $user->getUsername();
    }
}

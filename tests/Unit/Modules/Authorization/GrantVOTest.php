<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Module\Authorization\ValueObject\GrantVO;
use PHPUnit\Framework\TestCase;

class GrantVOTest extends TestCase
{
    public function testShouldGetSlug(): void
    {
        // Given
        $grant01 = GrantVO::m('someGrant');
        $grant02 = GrantVO::m('someGrant:');

        // Then
        $this->assertEquals('someGrant', $grant01->getBaseSlug());
        $this->assertEquals('someGrant', $grant02->getBaseSlug());
    }

    public function testShouldThrowExceptionWhenGrantSlugCannotBeResolved(): void
    {
        // Expect
        $this->expectException(\Exception::class);

        // When
        GrantVO::m('');
    }

    public function testShouldOptionSlug(): void
    {
        // Given
        $grant01 = GrantVO::m('someGrant:someOptionValue');

        // Then
        $this->assertEquals('someGrant', $grant01->getBaseSlug());
        $this->assertEquals('someOptionValue', $grant01->getOptionSlug());
    }
}
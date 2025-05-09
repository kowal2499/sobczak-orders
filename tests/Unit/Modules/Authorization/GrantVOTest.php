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
        $grant03 = GrantVO::m('someGrant:option=');
        $grant04 = GrantVO::m('someGrant:option=true');

        // Then
        $this->assertEquals('someGrant', $grant01->getSlug());
        $this->assertEquals('someGrant', $grant02->getSlug());
        $this->assertEquals('someGrant', $grant03->getSlug());
        $this->assertEquals('someGrant', $grant04->getSlug());
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
        $grant02 = GrantVO::m('someGrant:someOptionValue=false');
        $grant03 = GrantVO::m('someGrant:someOptionValue=other');

        // Then
        $this->assertEquals('someGrant', $grant01->getSlug());
        $this->assertEquals('someOptionValue', $grant02->getOptionSlug());
        $this->assertEquals('someOptionValue', $grant03->getOptionSlug());
    }

    public function testShouldGrantValue(): void
    {
        // Given
        $grant01 = GrantVO::m('someGrant:someOptionValue');
        $grant02 = GrantVO::m('someGrant:someOptionValue=');
        $grant03 = GrantVO::m('someGrant:someOptionValue=false');
        $grant04 = GrantVO::m('someGrant:someOptionValue=true');
        $grant05 = GrantVO::m('someGrant:someOptionValue=other');

        // Then
        $this->assertFalse($grant01->getValue());
        $this->assertFalse($grant02->getValue());
        $this->assertFalse($grant03->getValue());
        $this->assertTrue($grant04->getValue());
        $this->assertFalse($grant05->getValue());
    }
}
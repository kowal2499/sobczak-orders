<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Module\Authorization\ValueObject\GrantVO;
use PHPUnit\Framework\TestCase;

class GrantVOTest extends TestCase
{
    public function testShouldGetSlug(): void
    {
        // Given
        $grant01 = GrantVO::fromString('someGrant');
        $grant02 = GrantVO::fromString('someGrant:=');

        // Then
        $this->assertEquals('someGrant', $grant01->getSlug());
        $this->assertEquals('someGrant', $grant02->getSlug());
    }

    public function testShouldThrowExpectionWhenGrantSlugCannotBeResolved(): void
    {
        // Expect
        $this->expectException(\Exception::class);

        // When
        GrantVO::fromString('');
    }

    public function testShouldGetOptionValue(): void
    {
        // Given
        $grant01 = GrantVO::fromString('someGrant:someOptionValue');
        $grant02 = GrantVO::fromString('someGrant:someOptionValue=true');

        // Then
        $this->assertEquals('someOptionValue', $grant01->getOptionValue());
        $this->assertEquals('someOptionValue', $grant02->getOptionValue());
    }

    public function testShouldGetValue(): void
    {
        // Given
        $grant01 = GrantVO::fromString('someGrant');
        $grant02 = GrantVO::fromString('someGrant:someOptionValue');
        $grant03 = GrantVO::fromString('someGrant:someOptionValue=true');
        $grant04 = GrantVO::fromString('someGrant:someOptionValue=false');
        $grant05 = GrantVO::fromString('someGrant:someOptionValue=lorem');
        $grant06 = GrantVO::fromString('someGrant=true');
        $grant07 = GrantVO::fromString('someGrant=false');

        // Then
        $this->assertTrue($grant01->getValue());
        $this->assertTrue($grant02->getValue());
        $this->assertTrue($grant03->getValue());
        $this->assertFalse($grant04->getValue());
        $this->assertFalse($grant05->getValue());
        $this->assertTrue($grant06->getValue());
        $this->assertFalse($grant07->getValue());
    }
}
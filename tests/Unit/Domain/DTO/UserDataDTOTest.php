<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\DTO;

use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;

/**
 * Test Driven Development - tests for UserDataDTO
 * Following SOLID principles with focused test responsibility.
 */
class UserDataDTOTest extends TestCase
{
    public function testCanCreateWithValidData(): void
    {
        $dto = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('Leanne Graham', $dto->name);
        $this->assertEquals('Sincere@april.biz', $dto->email);
        $this->assertEquals('Gwenborough', $dto->city);
        $this->assertEquals('Romaguera-Crona', $dto->company);
    }

    public function testJsonSerializeReturnsCorrectStructure(): void
    {
        $dto = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');

        $expected = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
            'city' => 'Gwenborough',
            'company' => 'Romaguera-Crona'
        ];

        $this->assertEquals($expected, $dto->jsonSerialize());
    }

    public function testDifferentInstancesHaveIndependentData(): void
    {
        $dto1 = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');
        $dto2 = new UserDataDTO(2, 'John Doe', 'john@example.com', 'Warsaw', 'ABC Corp');

        $this->assertNotEquals($dto1->id, $dto2->id);
        $this->assertNotEquals($dto1->name, $dto2->name);
        $this->assertNotEquals($dto1->email, $dto2->email);
        $this->assertNotEquals($dto1->city, $dto2->city);
        $this->assertNotEquals($dto1->company, $dto2->company);
    }

    public function testJsonEncodingWorks(): void
    {
        $dto = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');

        $json = json_encode($dto, JSON_THROW_ON_ERROR);

        $this->assertJson($json);

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals([
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
            'city' => 'Gwenborough',
            'company' => 'Romaguera-Crona'
        ], $decoded);
    }
}

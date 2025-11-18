<?php

declare(strict_types=1);

namespace App\Domain\DTO;

/**
 * Data Transfer Object for processed user data response
 * Following Single Responsibility Principle - only holds data, no logic
 */
final class UserDataDTO implements \JsonSerializable
{
    public function __construct(public readonly int $id, public readonly string $name, public readonly string $email, public readonly string $city, public readonly string $company)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
            'company' => $this->company,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class User
{
    public function __construct(
        private ?int $id,
        private string $username,
        private string $passwordHash,
        private DateTimeImmutable $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}

<?php

namespace App\Repositories\Contracts;

interface PasswordResetTokenRepositoryInterface
{
    public function replace(string $email, string $hashedToken): void;

    public function find(string $email): ?object;

    public function findForUpdate(string $email): ?object;

    public function delete(string $email): void;
}

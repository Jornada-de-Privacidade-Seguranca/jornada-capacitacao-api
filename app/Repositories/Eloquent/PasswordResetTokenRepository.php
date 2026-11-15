<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\PasswordResetTokenRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function replace(string $email, string $hashedToken): void
    {
        $table = (string) config('auth.passwords.users.table');
        DB::table($table)->where('email', $email)->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => now(),
        ]);
    }

    public function findForUpdate(string $email): ?object
    {
        $table = (string) config('auth.passwords.users.table');

        return DB::table($table)->where('email', $email)->lockForUpdate()->first();
    }

    public function find(string $email): ?object
    {
        $table = (string) config('auth.passwords.users.table');

        return DB::table($table)->where('email', $email)->first();
    }

    public function delete(string $email): void
    {
        $table = (string) config('auth.passwords.users.table');
        DB::table($table)->where('email', $email)->delete();
    }
}

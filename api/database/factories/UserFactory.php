<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password12345'),
            'role' => 'user',
            'kyc_status' => 'none',
            'is_frozen' => false,
            'totp_secret' => null,
            'totp_enabled' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_frozen' => true,
        ]);
    }

    public function kycApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => 'approved',
        ]);
    }

    public function with2FA(): static
    {
        return $this->state(fn (array $attributes) => [
            'totp_enabled' => true,
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mfa_secret')->nullable();
            $table->text('mfa_backup_codes')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->enum('kyc_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('is_frozen')->default(false);
            $table->timestamp('email_verified_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_backup_codes', 'mfa_enabled', 'kyc_status', 'role', 'is_frozen']);
        });
    }
};

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Asset;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $assets = Asset::where('is_active', true)->get();
        foreach ($assets as $asset) {
            Wallet::create([
                'user_id' => $user->id,
                'asset_id' => $asset->id,
                'balance_available' => 0,
                'balance_locked' => 0,
            ]);
        }

        AuditLog::log('user.registered', $user->id, 'User', $user->id);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'otp' => 'nullable|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            AuditLog::log('auth.login_failed', null, null, null, ['email' => $validated['email']]);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->is_frozen) {
            return response()->json(['message' => 'Account is frozen'], 403);
        }

        if ($user->mfa_enabled) {
            if (!isset($validated['otp'])) {
                return response()->json(['message' => '2FA code required', 'requires_2fa' => true], 200);
            }

            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($user->mfa_secret, $validated['otp']);
            
            if (!$valid) {
                return response()->json(['message' => 'Invalid 2FA code'], 401);
            }
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        AuditLog::log('auth.login', $user->id, 'User', $user->id);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLog::log('auth.logout', $user->id, 'User', $user->id);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('wallets.asset'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $request->user()->update($validated);

        return response()->json($request->user());
    }

    public function enable2FA(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->mfa_enabled) {
            return response()->json(['message' => '2FA is already enabled'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->update(['mfa_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function verify2FA(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        
        if (!$user->mfa_secret) {
            return response()->json(['message' => '2FA not set up'], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->mfa_secret, $validated['otp']);

        if (!$valid) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = bin2hex(random_bytes(4));
        }

        $user->update([
            'mfa_enabled' => true,
            'mfa_backup_codes' => $backupCodes,
        ]);

        AuditLog::log('auth.2fa_enabled', $user->id, 'User', $user->id);

        return response()->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes,
        ]);
    }

    public function disable2FA(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        
        if (!$user->mfa_enabled) {
            return response()->json(['message' => '2FA is not enabled'], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->mfa_secret, $validated['otp']);

        if (!$valid) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_backup_codes' => null,
        ]);

        AuditLog::log('auth.2fa_disabled', $user->id, 'User', $user->id);

        return response()->json(['message' => '2FA disabled successfully']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $token = Str::random(64);
            
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            AuditLog::log('auth.password_reset_requested', $user->id, 'User', $user->id);
        }

        return response()->json([
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$resetRecord) {
            return response()->json(['message' => 'Invalid reset token'], 400);
        }

        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return response()->json(['message' => 'Reset token has expired'], 400);
        }

        if (!Hash::check($validated['token'], $resetRecord->token)) {
            return response()->json(['message' => 'Invalid reset token'], 400);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        AuditLog::log('auth.password_reset', $user->id, 'User', $user->id);

        return response()->json(['message' => 'Password reset successfully']);
    }
}

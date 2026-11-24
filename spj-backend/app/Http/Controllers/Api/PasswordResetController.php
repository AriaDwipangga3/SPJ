<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Jika email terdaftar, kami sudah mengirimkan link reset password.',
            ]);
        }

        // Gagal kirim email (SMTP salah/mati) tidak boleh menampilkan error teknis ke pengguna
        // atau membedakan email terdaftar vs tidak; cukup dicatat di log.
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            \Log::error('Gagal mengirim email reset password: ' . $e->getMessage());
        }

        // ============ LOG ============
        ActivityLogger::log(
            'password_reset_request',
            'auth',
            "Minta link reset password untuk: {$request->email}",
            null,
            ['email' => $request->email],
            $request
        );

        return response()->json([
            'message' => 'Jika email terdaftar, kami sudah mengirimkan link reset password.',
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ], [
            'token.required' => 'Token reset tidak ditemukan.',
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.numbers' => 'Password harus mengandung angka.',
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // ============ LOG ============
            ActivityLogger::log(
                'password_reset',
                'auth',
                "Reset password berhasil untuk: {$request->email}",
                null,
                ['email' => $request->email],
                $request
            );

            return response()->json([
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ]);
        }

        $messages = [
            Password::INVALID_TOKEN => 'Link reset password tidak valid atau sudah kedaluwarsa.',
            Password::INVALID_USER => 'Email tidak terdaftar.',
            Password::RESET_THROTTLED => 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.',
        ];

        return response()->json([
            'message' => $messages[$status] ?? 'Gagal mereset password.',
        ], 422);
    }
}
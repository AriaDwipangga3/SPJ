<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update($data);

        // ============ LOG ============
        ActivityLogger::log(
            'profile_update',
            'profile',
            'Update profil: ' . $user->name,
            null,
            ['name' => $user->name],
            $request
        );

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->fresh(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
            'password.letters' => 'Password baru harus mengandung huruf.',
            'password.numbers' => 'Password baru harus mengandung angka.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        // ============ LOG ============
        ActivityLogger::log(
            'password_change',
            'profile',
            'Ganti password',
            null,
            null,
            $request
        );

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
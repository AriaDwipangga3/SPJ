<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        return $query
            ->orderBy('name')
            ->paginate($request->input('per_page', 10));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // ============ LOG ============
        ActivityLogger::log(
            'user_create',
            'user',
            "Tambah user baru: {$user->name} ({$user->email})",
            $user,
            ['email' => $user->email, 'role' => $user->role],
            $request
        );

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'in:admin,user'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        // ============ LOG ============
        ActivityLogger::log(
            'user_update',
            'user',
            "Edit user: {$user->name} ({$user->email})",
            $user,
            ['email' => $user->email, 'role' => $user->role],
            $request
        );

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri.',
            ], 422);
        }

        // ============ LOG (sebelum delete) ============
        ActivityLogger::log(
            'user_delete',
            'user',
            "Hapus user: {$user->name} ({$user->email})",
            $user,
            ['email' => $user->email],
            $request
        );

        $user->delete();

        return response()->json(['message' => 'User dihapus.']);
    }
}
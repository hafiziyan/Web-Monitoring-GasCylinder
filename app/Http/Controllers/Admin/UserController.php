<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount('transactions')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role') && in_array($request->string('role')->toString(), ['admin', 'operator'], true),
                fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->orderBy('name')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        AuditLogService::log(
            $request->user(),
            'CREATE_USER',
            'USER',
            "Akun {$user->email} ({$user->role->value}) dibuat.",
            $user,
            [],
            $user->only(['name', 'email', 'role', 'is_active']),
        );

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        // Proteksi akun sendiri: role & status tidak boleh diubah dari sini.
        if ($user->is($request->user())) {
            $data['role'] = $user->role->value;
            $data['is_active'] = $user->is_active;
        }

        // Jangan sampai tidak ada admin aktif tersisa (via ubah role).
        if ($user->role === UserRole::Admin
            && $data['role'] !== UserRole::Admin->value
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->exists()
        ) {
            return back()->withInput()->with('error', 'Tidak dapat menurunkan role: minimal harus ada satu admin aktif.');
        }

        // Jangan sampai tidak ada admin aktif tersisa (via nonaktifkan).
        if ($user->role === UserRole::Admin && $user->is_active
            && $data['is_active'] === false
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->exists()
        ) {
            return back()->withInput()->with('error', 'Tidak dapat menonaktifkan: minimal harus ada satu admin aktif.');
        }

        // Password kosong = tidak diubah.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $old = $user->getOriginal();
        $user->update($data);

        AuditLogService::log(
            $request->user(),
            'UPDATE_USER',
            'USER',
            "Akun {$user->email} diperbarui.",
            $user,
            collect($old)->only(['name', 'email', 'role', 'is_active'])->all(),
            $user->fresh()->only(['name', 'email', 'role', 'is_active']),
        );

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    public function toggle(User $user, Request $request): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        // Jangan nonaktifkan admin aktif terakhir.
        if ($user->role === UserRole::Admin && $user->is_active
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->exists()
        ) {
            return back()->with('error', 'Tidak dapat menonaktifkan: minimal harus ada satu admin aktif.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        AuditLogService::log(
            $request->user(),
            $user->is_active ? 'ACTIVATE_USER' : 'DEACTIVATE_USER',
            'USER',
            "Akun {$user->email} ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan.'),
            $user,
            [],
            $user->only(['email', 'is_active']),
        );

        return back()->with('success', "Akun {$user->name} berhasil ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        // Jangan hapus admin aktif terakhir.
        if ($user->role === UserRole::Admin
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->exists()
        ) {
            return back()->with('error', 'Tidak dapat menghapus: minimal harus ada satu admin aktif.');
        }

        if ($user->transactions()->exists()) {
            return back()->with('error', "Akun {$user->name} tidak dapat dihapus karena memiliki riwayat transaksi. Nonaktifkan saja.");
        }

        try {
            $email = $user->email;
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', "Akun {$user->name} tidak dapat dihapus karena masih terhubung dengan data lain. Nonaktifkan saja.");
        }

        AuditLogService::log(
            $request->user(),
            'DELETE_USER',
            'USER',
            "Akun {$email} dihapus.",
            null,
            ['email' => $email],
            [],
        );

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil dihapus.');
    }
}

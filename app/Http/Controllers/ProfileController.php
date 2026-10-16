<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Jangan biarkan sistem tanpa admin aktif.
        if ($user->role === UserRole::Admin
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->exists()
        ) {
            return Redirect::route('profile.edit')->with('error', 'Akun tidak dapat dihapus: minimal harus ada satu admin aktif.');
        }

        Auth::logout();

        try {
            $user->delete();
        } catch (QueryException) {
            return Redirect::to('/')->with('error', 'Akun tidak dapat dihapus karena masih terhubung dengan data transaksi. Minta admin untuk menonaktifkan akun ini.');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

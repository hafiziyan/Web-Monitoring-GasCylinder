<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => Settings::all(),
            'perPageOptions' => [10, 15, 20, 25, 50],
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $before = Settings::all();
        Settings::setMany($data);

        AuditLogService::log(
            $request->user(),
            'UPDATE_SETTINGS',
            'USER',
            'Pengaturan aplikasi diperbarui.',
            null,
            $before,
            Settings::all(),
        );

        return redirect()->route('admin.settings')->with('success', 'Pengaturan berhasil disimpan dan langsung berlaku.');
    }
}

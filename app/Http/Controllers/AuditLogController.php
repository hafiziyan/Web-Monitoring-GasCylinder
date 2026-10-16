<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $auditLogs = AuditLog::with('user')
            ->when($request->filled('search'), fn ($query) => $query
                ->where('description', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('user_id'), fn ($query) => $query
                ->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($query) => $query
                ->where('action', $request->string('action')->toString()))
            ->when($request->filled('module'), fn ($query) => $query
                ->where('module', $request->string('module')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query
                ->where('created_at', '>=', $request->date('date_from')->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query
                ->where('created_at', '<=', $request->date('date_to')->endOfDay()))
            ->orderByDesc('created_at')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('audit_logs.index', [
            'auditLogs' => $auditLogs,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
            'modules' => AuditLog::distinct()->orderBy('module')->pluck('module'),
            'filters' => $request->only(['search', 'user_id', 'action', 'module', 'date_from', 'date_to']),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('audit_logs.show', compact('auditLog'));
    }
}

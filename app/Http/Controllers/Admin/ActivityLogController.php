<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ActivityLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }

        $logs = $query->paginate(25)->withQueryString()->through(fn (ActivityLog $log) => [
            'id' => $log->id,
            'user' => $log->user?->name,
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'description' => $log->description,
            'created_at' => $log->created_at?->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Admin/Activity/Index', [
            'logs' => $logs,
            'filters' => [
                'user_id' => $request->input('user_id'),
                'action' => $request->input('action'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}

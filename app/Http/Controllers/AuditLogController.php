<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Journal d'audit (cahier §32).
 *
 * Lecture seule : le journal n'est ni modifiable ni supprimable. La route
 * est protégée par `permission:audit.view`.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $action = (string) $request->query('action', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $logs = AuditLog::query()
            ->with(['user', 'bakery'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                /** @var Builder<AuditLog> $query */
                $query->search($search);
            })
            ->when($action !== '', function (Builder $query) use ($action): void {
                /** @var Builder<AuditLog> $query */
                $query->where('action', $action);
            })
            ->when($from !== '', function (Builder $query) use ($from): void {
                /** @var Builder<AuditLog> $query */
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($to !== '', function (Builder $query) use ($to): void {
                /** @var Builder<AuditLog> $query */
                $query->whereDate('created_at', '<=', $to);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        /** @var list<string> $actions */
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action')->all();

        return view('settings.audit.index', [
            'logs' => $logs,
            'search' => $search,
            'action' => $action,
            'from' => $from,
            'to' => $to,
            'actions' => $actions,
        ]);
    }
}

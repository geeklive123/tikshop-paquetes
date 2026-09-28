<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrintJobController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PrintJob::class);

        /** @var User $user */
        $user = $request->user();
        $jobs = PrintJob::query()
            ->forCompany($user->company)
            ->when($user->role === UserRole::Operator, fn ($query) => $query->where('requested_by', $user->id))
            ->with(['package:id,ulid,tracking_code', 'printer:id,name', 'requestedBy:id,name'])
            ->latest()
            ->paginate(20);
        $agents = Gate::allows('viewAny', PrinterAgent::class)
            ? PrinterAgent::query()
                ->where('company_id', $user->company_id)
                ->with('branch:id,name')
                ->orderBy('name')
                ->get()
            : collect();

        return view('print-jobs.index', compact('jobs', 'agents'));
    }
}

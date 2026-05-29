<?php

namespace App\Http\Middleware;

use App\Enums\SalesTeam;
use App\Services\SalesTeamService;
use App\Support\AdminHomeResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesTeamPipeline
{
    public function __construct(
        private readonly SalesTeamService $teams,
    ) {
    }

    /** Usage: middleware('sales.pipeline:candidate|employer') */
    public function handle(Request $request, Closure $next, string $pipeline): Response
    {
        $admin = $request->user('admin') ?? $request->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $required = SalesTeam::from($pipeline);

        if ($this->teams->canAccessPipeline($admin, $required)) {
            return $next($request);
        }

        $home = AdminHomeResolver::urlFor($admin);

        return redirect($home)
            ->with('info', 'That page is for the '
                .($required === SalesTeam::Candidate ? 'Talent (candidate)' : 'Company (employer)')
                .' team. You’ve been sent to your home screen.');
    }
}

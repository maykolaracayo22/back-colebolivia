<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Acta::class => \App\Policies\ActaPolicy::class,
        \App\Models\ActaNote::class => \App\Policies\ActaNotePolicy::class,
        \App\Models\ActaEvaluation::class => \App\Policies\ActaEvaluationPolicy::class,
        \App\Models\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \App\Models\ConductIncident::class => \App\Policies\ConductIncidentPolicy::class,
        \App\Models\Attendance::class => \App\Policies\AttendancePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('SuperAdmin') || $user->hasRole('Director General')) {
                return true; // da acceso a TODO a esos roles
            }
        });
    }

}

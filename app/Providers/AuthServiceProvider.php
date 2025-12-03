<?php

namespace App\Providers;

use App\Models\CaseModel;
use App\Policies\CasePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings.
     */
    protected $policies = [
    \App\Models\CaseModel::class => \App\Policies\CasePolicy::class,
    ];

    public function boot(): void
    {
            $this->registerPolicies();

    }
}

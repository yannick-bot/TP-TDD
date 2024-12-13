<?php

namespace App\Providers;

use App\Policies\ChirpPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    protected $policies = [Chirp::class => ChirpPolicy::class,];
    public function boot()
    {
        $this->registerPolicies();
        Gate::define('like', [ChirpPolicy::class, 'like']);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\FormRepositoryInterface;
use App\Repositories\FormRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FormRepositoryInterface::class, FormRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()->header('X-Forwarded-Proto') === 'https') {
            \URL::forceScheme('https');
        }
    }
}

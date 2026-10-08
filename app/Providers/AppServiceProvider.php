<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use App\Infrastructure\Reservations\EloquentReservationRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 綁定 Repository 接口到實現
        $this->app->bind(ReservationRepositoryInterface::class, EloquentReservationRepository::class);

        // 註冊 TenantContext 作為單例
        $this->app->singleton(\App\Support\TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Manually register policies
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}

<?php

namespace App\Providers;

use App\Http\Middleware\ActiveAccount;
use App\Models\School;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('inventory.school', fn () => School::query()->first());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (config('inventory.permissions') as $name) {
            Gate::define($name, fn (User $user) => $user->hasPermission($name));
        }
        Paginator::useTailwind();
        Livewire::addPersistentMiddleware([ActiveAccount::class]);
    }
}

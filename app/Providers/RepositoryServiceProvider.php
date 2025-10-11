<?php

namespace App\Providers;

use App\Interfaces\FloorRepositoryInterface;
use App\Interfaces\PropertyRepositoryInterface;
use App\Interfaces\RoomRepositoryInterface;
use App\Interfaces\TenantRepositoryInterface;
use App\Repositories\FloorRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\RoomRepository;
use App\Repositories\TenantRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PropertyRepositoryInterface::class, PropertyRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(FloorRepositoryInterface::class, FloorRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

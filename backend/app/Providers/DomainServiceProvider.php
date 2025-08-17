<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\domain\Shared\Clock\ClockInterface;
use App\domain\Shared\Id\UuidGeneratorInterface;
use App\infra\Shared\Clock\SystemClock;
use App\infra\Shared\Id\UuidGenerator;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 基盤サービスのバインド
        $this->app->bind(ClockInterface::class, SystemClock::class);
        $this->app->bind(UuidGeneratorInterface::class, UuidGenerator::class);

        // SystemClockの設定
        $this->app->when(SystemClock::class)
            ->needs('$timezone')
            ->give('Asia/Tokyo');

        // Repository interfaces は Phase3で追加予定
        // GroupRepositoryInterface::class => EloquentGroupRepository::class
        // ChildrenRepositoryInterface::class => EloquentChildrenRepository::class
        // StockRepositoryInterface::class => EloquentStockRepository::class
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
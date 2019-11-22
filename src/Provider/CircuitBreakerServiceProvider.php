<?php

namespace atlas\LaravelCircuitBreaker\Provider;

use atlas\LaravelCircuitBreaker\Store\CacheCircuitBreakerStore;
use atlas\LaravelCircuitBreaker\Store\CircuitBreakerStoreInterface;
use Illuminate\Support\ServiceProvider;

class CircuitBreakerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/circuit_breaker.php' => config_path('circuit_breaker.php'),
        ]);
    }

    public function register()
    {
        $this->app->bind(
            CircuitBreakerStoreInterface::class, 
            CacheCircuitBreakerStore::class
        );
    }
}

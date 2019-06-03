<?php

namespace Lauthz;

use Illuminate\Support\ServiceProvider;
use Lauthz\Models\Rule;
use Lauthz\Observers\RuleObserver;

class LauthzServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'laravel-lauthz-migrations');
            $this->publishes([__DIR__ . '/../config/lauthz-rbac-model.conf' => config_path('lauthz-rbac-model.conf')], 'laravel-lauthz-config');
            $this->publishes([__DIR__ . '/../config/lauthz.php' => config_path('lauthz.php')], 'laravel-lauthz-config');

            $this->commands([
                Commands\GroupAdd::class,
                Commands\PolicyAdd::class,
                Commands\RoleAssign::class,
            ]);
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/lauthz.php', 'lauthz');

        $this->bootObserver();
    }

    /**
     * Boot Observer.
     *
     * @return void
     */
    protected function bootObserver()
    {
        Rule::observe(new RuleObserver());
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->app->singleton('enforcer', function ($app) {
            return new EnforcerManager($app);
        });
    }
}

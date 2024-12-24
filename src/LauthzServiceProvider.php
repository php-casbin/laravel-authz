<?php

namespace Lauthz;

use Illuminate\Support\ServiceProvider;
use Lauthz\EnforcerLocalizer;
use Lauthz\Loaders\ModelLoaderManager;
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
            $this->publishes([
                __DIR__ . '/../config/lauthz-rbac-model.conf' => $this->app->basePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . ('lauthz-rbac-model.conf'),
                __DIR__ . '/../config/lauthz.php' => $this->app->basePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . ('lauthz.php'),
            ], 'laravel-lauthz-config');

            $this->commands([
                Commands\GroupAdd::class,
                Commands\PolicyAdd::class,
                Commands\RoleAssign::class,
            ]);
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/lauthz.php', 'lauthz');

        $this->bootObserver();

        $this->registerLocalizer();
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
        $this->app->singleton('enforcer', fn ($app) => new EnforcerManager($app));

        $this->app->singleton(ModelLoaderManager::class, fn ($app) => new ModelLoaderManager($app));

        $this->app->singleton(EnforcerLocalizer::class, fn ($app) => new EnforcerLocalizer($app));
    }

    /**
     * Register a gate that allows users to use Laravel's built-in Gate to call Enforcer.
     *
     * @return void
     */
    protected function registerLocalizer()
    {
        $this->app->make(EnforcerLocalizer::class)->register();
    }
}

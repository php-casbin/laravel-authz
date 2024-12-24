<?php

namespace Lauthz;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Application;
use Lauthz\Facades\Enforcer;

class EnforcerLocalizer
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new localizer instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register the localizer based on the configuration.
     */
    public function register()
    {
        if ($this->app->config->get('lauthz.localizer.enabled_register_at_gates')) {
            $this->registerAtGate();
        }
    }

    /**
     * Register the localizer at the gate.
     */
    protected function registerAtGate()
    {
        $this->app->make(Gate::class)->before(function (Authorizable $user, string $ability, array $guards) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
            $identifier = $user->getAuthIdentifier();
            if (method_exists($user, 'getAuthzIdentifier')) {
                /** @var \Lauthz\Tests\Models\User $user */
                $identifier = $user->getAuthzIdentifier();
            }
            $identifier = strval($identifier);
            $ability = explode(',', $ability);
            if (empty($guards)) {
                return Enforcer::enforce($identifier, ...$ability);
            }

            foreach ($guards as $guard) {
                return Enforcer::guard($guard)->enforce($identifier, ...$ability);
            }
        });
    }
}

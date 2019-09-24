<?php

namespace Lauthz;

use Casbin\Bridge\Logger\LoggerBridge;
use Casbin\Enforcer;
use Casbin\Model\Model;
use Casbin\Log\Log;
use Lauthz\Contracts\Factory;
use Lauthz\Models\Rule;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * @mixin \Casbin\Enforcer
 */
class EnforcerManager implements Factory
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of created "guards".
     *
     * @var array
     */
    protected $guards = [];

    /**
     * Create a new manager instance.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the enforcer from the local cache.
     *
     * @param string $name
     *
     * @return \Casbin\Enforcer
     *
     * @throws \InvalidArgumentException
     */
    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultGuard();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->resolve($name);
        }

        return $this->guards[$name];
    }

    /**
     * Resolve the given guard.
     *
     * @param string $name
     *
     * @return \Casbin\Enforcer
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Enforcer [{$name}] is not defined.");
        }

        if ($logger = Arr::get($config, 'log.logger')) {
            if (is_string($logger)) {
                $logger = $this->app->make($logger);
            }

            Log::setLogger(new LoggerBridge($logger));
        }

        $model = new Model();
        $configType = Arr::get($config, 'model.config_type');
        if ('file' == $configType) {
            $model->loadModel(Arr::get($config, 'model.config_file_path', ''));
        } elseif ('text' == $configType) {
            $model->loadModelFromText(Arr::get($config, 'model.config_text', ''));
        }
        $adapter = Arr::get($config, 'adapter');
        if (!is_null($adapter)) {
            $adapter = $this->app->make($adapter, [
                'eloquent' => new Rule([], $name),
            ]);
        }

        return new Enforcer($model, $adapter, Arr::get($config, 'log.enabled', false));
    }

    /**
     * Get the lauthz driver configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["lauthz.{$name}"];
    }

    /**
     * Get the default enforcer guard name.
     *
     * @return string
     */
    public function getDefaultGuard()
    {
        return $this->app['config']['lauthz.default'];
    }

    /**
     * Set the default guard driver the factory should serve.
     *
     * @param string $name
     */
    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultGuard();

        $this->setDefaultGuard($name);
    }

    /**
     * Set the default authorization guard name.
     *
     * @param string $name
     */
    public function setDefaultGuard($name)
    {
        $this->app['config']['lauthz.default'] = $name;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->guard()->{$method}(...$parameters);
    }
}

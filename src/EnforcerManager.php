<?php

namespace Lauthz;

use Casbin\Enforcer;
use Casbin\Model\Model;
use Casbin\Log\Log;
use Lauthz\Contracts\Factory;
use Illuminate\Support\Manager;
use Illuminate\Support\Arr;

/**
 * @mixin \Casbin\Enforcer
 */
class EnforcerManager extends Manager implements Factory
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['lauthz.default'];
    }

    /**
     * Create an instance of the Basic Enforcer driver.
     *
     * @param array $config
     *
     * @return \Casbin\Enforcer
     */
    public function createBasicDriver()
    {
        $config = $this->getConfig('basic');

        if ($logger = Arr::get($config, 'log.logger')) {
            Log::setLogger(new $logger($this->app['log']));
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
            $adapter = $this->app->make($adapter);
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
}

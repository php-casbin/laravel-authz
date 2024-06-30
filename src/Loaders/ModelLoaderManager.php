<?php

namespace Lauthz\Loaders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * The model loader manager.
 *
 * A model loader is responsible for a loading model from an arbitrary source.
 * Developers can customize loading behavior by implementing
 * and register the custom loader in AppServiceProvider through `app(LoaderManager::class)->extend()`.
 *
 * Built-in loader implementations include:
 *  - FileLoader: For loading model from file.
 *  - TextLoader: Suitable for model defined as a multi-line string.
 *  - UrlLoader: Handles model loading from URL.
 *
 *  To utilize a built-in or custom loader, set 'model.config_type' in the configuration to match one of the above types.
 */
class ModelLoaderManager extends Manager
{

    /**
     * The array of the lauthz driver configuration.
     * 
     * @var array
     */
    protected $config;

    /**
     * Initialize configuration for the loader manager instance.
     *
     * @param array $config the lauthz driver configuration.
     */
    public function initFromConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the default driver from the configuration.
     *
     * @return string The default driver name.
     */
    public function getDefaultDriver()
    {
        return Arr::get($this->config, 'model.config_type', '');
    }

    /**
     * Create a new TextLoader instance.
     *
     * @return TextLoader
     */
    public function createTextDriver()
    {
        return new TextLoader($this->config);
    }

    /**
     * Create a new UrlLoader instance.
     *
     * @return UrlLoader
     */
    public function createUrlDriver()
    {
        return new UrlLoader($this->config);
    }

    /**
     * Create a new FileLoader instance.
     *
     * @return FileLoader
     */
    public function createFileDriver()
    {
        return new FileLoader($this->config);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        if(empty($driver)) {
            throw new InvalidArgumentException('Unsupported empty model loader type.');
        }

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }
        $method = 'create' . Str::studly($driver) . 'Driver';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Unsupported model loader type: {$driver}.");
    }
}

<?php

namespace Lauthz\Loaders;

use Illuminate\Support\Arr;
use Lauthz\Contracts\Factory;
use InvalidArgumentException;

class ModelLoaderFactory implements Factory
{
    /**
     * Create a model loader from configuration.
     *
     * A model loader is responsible for a loading model from an arbitrary source.
     * Developers can customize loading behavior by implementing
     * the ModelLoader interface and specifying their custom class
     * via 'model.config_loader_class' in the configuration.
     *
     * Built-in loader implementations include:
     *  - FileLoader: For loading model from file.
     *  - TextLoader: Suitable for model defined as a multi-line string.
     *  - UrlLoader: Handles model loading from URL.
     *
     *  To utilize a built-in loader, set 'model.config_type' to match one of the above types.
     *
     * @param array $config
     * @return \Lauthz\Contracts\ModelLoader
     * @throws InvalidArgumentException
     */
    public static function createFromConfig(array $config) {
        $customLoader = Arr::get($config, 'model.config_loader_class', '');
        if (class_exists($customLoader)) {
            return new $customLoader($config);
        }

        $loaderType =  Arr::get($config, 'model.config_type', '');
        switch ($loaderType) {
            case 'file':
                return new FileLoader($config);
            case 'text':
                return new TextLoader($config);
            case 'url':
                return new UrlLoader($config);
            default:
                throw new InvalidArgumentException("Unsupported model loader type: {$loaderType}");
        }
    }
}
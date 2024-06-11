<?php

namespace Lauthz\Loaders;

use Casbin\Model\Model;
use Illuminate\Support\Arr;
use Lauthz\Contracts\ModelLoader;

class FileLoader implements ModelLoader
{
    /**
     * The path to the model file.
     *
     * @var string
     */
    private $filePath;

    /**
     * Constructor to initialize the file path.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->filePath = Arr::get($config, 'model.config_file_path', '');
    }

    /**
     * Loads model from file.
     *
     * @param Model $model
     * @return void
     * @throws \Casbin\Exceptions\CasbinException
     */
    public function loadModel(Model $model): void
    {
        $model->loadModel($this->filePath);
    }
}
<?php

namespace Lauthz\Loaders;

use Casbin\Model\Model;
use Illuminate\Support\Arr;
use Lauthz\Contracts\ModelLoader;

class TextLoader implements ModelLoader
{
    /**
     * Model text.
     *
     * @var string
     */
    private $text;

    /**
     * Constructor to initialize the model text.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->text = Arr::get($config, 'model.config_text', '');
    }

    /**
     * Loads model from text.
     *
     * @param Model $model
     * @return void
     * @throws \Casbin\Exceptions\CasbinException
     */
    public function loadModel(Model $model): void
    {
//        dd($this->text);
        $model->loadModelFromText($this->text);
    }
}
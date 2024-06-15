<?php

namespace Lauthz\Loaders;

use Casbin\Model\Model;
use Illuminate\Support\Arr;
use Lauthz\Contracts\ModelLoader;
use RuntimeException;

class UrlLoader implements ModelLoader
{
    /**
     * The url to fetch the remote model string.
     *
     * @var string
     */
    private $url;

    /**
     * Constructor to initialize the url path.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->url = Arr::get($config, 'model.config_url', '');
    }

    /**
     * Loads model from remote url.
     *
     * @param Model $model
     * @return void
     * @throws \Casbin\Exceptions\CasbinException
     * @throws RuntimeException
     */
    public function loadModel(Model $model): void
    {
        $contextOptions = [
            'http' => [
                'method'  => 'GET',
                'header'  => "Accept: text/plain\r\n",
                'timeout' => 3
            ]
        ];

        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($this->url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            throw new RuntimeException(
                "Failed to fetch remote model " . $this->url . ": " . $error['message']
            );
        }

        $model->loadModelFromText($response);
    }
}
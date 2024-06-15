<?php

namespace Lauthz\Contracts;


use Casbin\Model\Model;

interface  ModelLoader
{
    /**
     * Loads model definitions into the provided model object.
     *
     * @param Model $model
     * @return void
     */
    function loadModel(Model $model): void;
}
<?php

namespace Lauthz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Rule Model.
 */
class Rule extends Model
{
    /**
     * a cache store.
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $store;

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = ['ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = $this->config('database.connection') ?: config('database.default');

        $this->setConnection($connection);
        $this->setTable($this->config('database.rules_table'));

        parent::__construct($attributes);

        $this->initCache();
    }

    /**
     * Gets rules from caches.
     *
     * @return mixed
     */
    public function getAllFromCache()
    {
        $get = function () {
            return $this->select('ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5')->get()->toArray();
        };
        if (!$this->config('cache.enabled', false)) {
            return $get();
        }

        return $this->store->remember($this->config('cache.key'), $this->config('cache.ttl'), $get);
    }

    /**
     * Refresh Cache.
     */
    public function refreshCache()
    {
        if (!$this->config('cache.enabled', false)) {
            return;
        }

        $this->forgetCache();
        $this->getAllFromCache();
    }

    /**
     * Forget Cache.
     */
    public function forgetCache()
    {
        $this->store->forget($this->config('cache.key'));
    }

    /**
     * Init cache.
     */
    protected function initCache()
    {
        $driver = config('lauthz.default');
        $store = $this->config('cache.store', 'default');
        $store = 'default' == $store ? null : $store;
        $this->store = Cache::store($store);
    }

    /**
     * Gets config value by key.
     *
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    protected function config($key = null, $default = null)
    {
        $driver = config('lauthz.default');

        return config('lauthz.'.$driver.'.'.$key, $default);
    }
}

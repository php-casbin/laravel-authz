<?php

namespace Lauthz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Rule Model.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Rule>
 * @property string $ptype
 * @property string $v0
 * @property string $v1
 * @property string $v2
 * @property string $v3
 * @property string $v4
 * @property string $v5
 */
class Rule extends Model
{
    /**
     * a cache store.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $store;

    /**
     * the guard for lauthz.
     *
     * @var string
     */
    protected $guard;

    /**
     * Fillable.
     *
     * @var list<string>
     */
    protected $fillable = ['ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array<string, mixed>  $attributes
     * @param string $guard
     */
    public function __construct(array $attributes = [], $guard = '')
    {
        $this->guard = $guard;
        if (!$guard) {
            $this->guard = config('lauthz.default');
        }

        $connection = $this->config('database.connection') ?: config('database.default');

        $this->setConnection($connection);
        $this->setTable($this->config('database.rules_table'));

        parent::__construct($attributes);

        $this->initCache();
    }

    /**
     * Gets rules from caches.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllFromCache(): array
    {
        $get = fn () => $this->select('ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5')->get()->toArray();
        if (!$this->config('cache.enabled', false)) {
            return $get();
        }

        return $this->store->remember($this->getCacheKey(), $this->config('cache.ttl'), $get);
    }

    /**
     * Gets the cache key, scoped to the enforcer guard so that multiple
     * enforcers never read or overwrite each other's cached rules.
     *
     * @return string
     */
    private function getCacheKey(): string
    {
        return $this->guard.':'.$this->config('cache.key', 'rules');
    }

    /**
     * Refresh Cache.
     */
    public function refreshCache(): void
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
    public function forgetCache(): void
    {
        $this->store->forget($this->getCacheKey());
    }

    /**
     * Init cache.
     */
    protected function initCache(): void
    {
        $store = $this->config('cache.store', 'default');
        $store = 'default' == $store ? null : $store;
        $this->store = Cache::store($store);
    }

    /**
     * Fire a model event for the given model.
     *
     * @param string $event
     * @param bool $halt
     * @return mixed
     */
    public function fireModelEvent($event, $halt = true)
    {
        return parent::fireModelEvent($event, $halt);
    }

    /**
     * Gets config value by key.
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function config($key = null, $default = null)
    {
        return config('lauthz.'.$this->guard.'.'.$key, $default);
    }
}

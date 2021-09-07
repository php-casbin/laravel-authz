<?php

declare(strict_types=1);

namespace Lauthz\Adapters;

use Lauthz\Models\Rule;
use Lauthz\Contracts\DatabaseAdapter as DatabaseAdapterContract;
use Lauthz\Contracts\BatchDatabaseAdapter as BatchDatabaseAdapterContract;
use Lauthz\Contracts\UpdatableDatabaseAdapter as UpdatableDatabaseAdapterContract;
use Lauthz\Contracts\FilteredDatabaseAdapter as FilteredDatabaseAdapterContract;
use Casbin\Persist\Adapters\Filter;
use Casbin\Model\Model;
use Casbin\Persist\AdapterHelper;
use DateTime;
use Casbin\Exceptions\InvalidFilterTypeException;
use Illuminate\Support\Facades\DB;

/**
 * DatabaseAdapter.
 *
 * @author techlee@qq.com
 */
class DatabaseAdapter implements DatabaseAdapterContract, BatchDatabaseAdapterContract, UpdatableDatabaseAdapterContract, FilteredDatabaseAdapterContract
{
    use AdapterHelper;

    /**
     * @var bool
     */
    private $filtered = false;

    /**
     * Rules eloquent model.
     *
     * @var Rule
     */
    protected $eloquent;

    /**
     * the DatabaseAdapter constructor.
     *
     * @param Rule $eloquent
     */
    public function __construct(Rule $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    /**
     * savePolicyLine function.
     *
     * @param string $ptype
     * @param array  $rule
     */
    public function savePolicyLine(string $ptype, array $rule): void
    {
        $col['ptype'] = $ptype;
        foreach ($rule as $key => $value) {
            $col['v'.strval($key)] = $value;
        }

        $this->eloquent->create($col);
    }

    /**
     * loads all policy rules from the storage.
     *
     * @param Model $model
     */
    public function loadPolicy(Model $model): void
    {
        $rows = $this->eloquent->getAllFromCache();

        foreach ($rows as $row) {
            $line = implode(', ', array_filter($row, function ($val) {
                return '' != $val && !is_null($val);
            }));
            $this->loadPolicyLine(trim($line), $model);
        }
    }

    /**
     * saves all policy rules to the storage.
     *
     * @param Model $model
     */
    public function savePolicy(Model $model): void
    {
        foreach ($model['p'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $this->savePolicyLine($ptype, $rule);
            }
        }

        foreach ($model['g'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $this->savePolicyLine($ptype, $rule);
            }
        }
    }

    /**
     * adds a policy rule to the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param array  $rule
     */
    public function addPolicy(string $sec, string $ptype, array $rule): void
    {
        $this->savePolicyLine($ptype, $rule);
    }

     /**
     * Adds a policy rules to the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param string[][] $rules
     */
    public function addPolicies(string $sec, string $ptype, array $rules): void
    {
        $cols = [];
        $i = 0;

        foreach($rules as $rule) {
            $temp['ptype'] = $ptype;
            $temp['created_at'] = new DateTime();
            $temp['updated_at'] = $temp['created_at'];
            foreach ($rule as $key => $value) {
                $temp['v'.strval($key)] = $value;
            }
            $cols[$i++] = $temp ?? [];
            $temp = [];
        }
        $this->eloquent->insert($cols);
        Rule::fireModelEvent('saved');
    }

    /**
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param array  $rule
     */
    public function removePolicy(string $sec, string $ptype, array $rule): void
    {
        $instance = $this->eloquent->where('ptype', $ptype);

        foreach ($rule as $key => $value) {
            $instance->where('v'.strval($key), $value);
        }

        $instance->delete();
        Rule::fireModelEvent('deleted');
    }

    /**
     * Removes policy rules from the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param string[][] $rules
     */
    public function removePolicies(string $sec, string $ptype, array $rules): void
    {
        DB::transaction(function () use ($sec, $rules, $ptype) {
            foreach ($rules as $rule) {
                $this->removePolicy($sec, $ptype, $rule);
            }
        });
    }

    /**
     * RemoveFilteredPolicy removes policy rules that match the filter from the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param int    $fieldIndex
     * @param string ...$fieldValues
     */
    public function removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, string ...$fieldValues): void
    {
        $instance = $this->eloquent->where('ptype', $ptype);
        
        foreach (range(0, 5) as $value) {
            if ($fieldIndex <= $value && $value < $fieldIndex + count($fieldValues)) {
                if ('' != $fieldValues[$value - $fieldIndex]) {
                    $instance->where('v'.strval($value), $fieldValues[$value - $fieldIndex]);
                }
            }
        }
        
        $instance->delete();
        Rule::fireModelEvent('deleted');
    }

    /**
     * Updates a policy rule from storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param string[] $oldRule
     * @param string[] $newPolicy
     */
    public function updatePolicy(string $sec, string $ptype, array $oldRule, array $newPolicy): void
    {
        $instance = $this->eloquent->where('ptype', $ptype);
        foreach($oldRule as $k => $v) {
            $instance->where('v' . $k, $v);
        }
        $instance->first();
        $update = [];
        foreach($newPolicy as $k => $v) {
            $update['v' . $k] = $v;
        }
        $instance->update($update);
        Rule::fireModelEvent('saved');
    }

    /**
     * UpdatePolicies updates some policy rules to storage, like db, redis.
     *
     * @param string $sec
     * @param string $ptype
     * @param string[][] $oldRules
     * @param string[][] $newRules
     * @return void
     */
    public function updatePolicies(string $sec, string $ptype, array $oldRules, array $newRules): void
    {
        DB::transaction(function () use ($sec, $ptype, $oldRules, $newRules) {
            foreach ($oldRules as $i => $oldRule) {
                $this->updatePolicy($sec, $ptype, $oldRule, $newRules[$i]);
            }
        });
    }

    /**
     * UpdateFilteredPolicies deletes old rules and adds new rules.
     *
     * @param string $sec
     * @param string $ptype
     * @param array $newPolicies
     * @param integer $fieldIndex
     * @param string ...$fieldValues
     * @return array
     */
    public function updateFilteredPolicies(string $sec, string $ptype, array $newPolicies, int $fieldIndex, string ...$fieldValues): array
    {
        $where['ptype'] = $ptype;
        foreach ($fieldValues as $fieldValue) {
            if (!is_null($fieldValue) && $fieldValue !== '') {
                $where['v'. $fieldIndex++] = $fieldValue;
            }
        }

        $newP = [];
        $oldP = [];
        foreach ($newPolicies as $newRule) {
            $col['ptype'] = $ptype;
            $col['created_at'] = new DateTime();
            $col['updated_at'] = $col['created_at'];
            foreach ($newRule as $key => $value) {
                $col['v' . strval($key)] = $value;
            }
            $newP[] = $col;
        }

        DB::transaction(function () use ($newP, $where, &$oldP) {
            $oldRules = $this->eloquent->where($where);
            $oldP = $oldRules->get()->makeHidden(['created_at','updated_at', 'id'])->toArray();

            foreach ($oldP as &$item) {
                $item = array_filter($item, function ($value) {
                    return !is_null($value) && $value !== '';
                });
                unset($item['ptype']);
            }
            
            $oldRules->delete();
            $this->eloquent->insert($newP);
        });

        Rule::fireModelEvent('saved');

        // return deleted rules
        return $oldP;
    }

    /**
     * Loads only policy rules that match the filter.
     *
     * @param Model $model
     * @param mixed $filter
     */
    public function loadFilteredPolicy(Model $model, $filter): void
    {
        $instance = $this->eloquent;

        if (is_string($filter)) {
            $instance = $instance->whereRaw($filter);
        } else if ($filter instanceof Filter) {
            foreach($filter->p as $k => $v) {
                $where[$v] = $filter->g[$k];
                $instance = $instance->where($v, $filter->g[$k]);
            }
        } else if ($filter instanceof \Closure) {
            $instance = $instance->where($filter);
        } else {
            throw new InvalidFilterTypeException('invalid filter type');
        }
        $rows = $instance->get()->makeHidden(['created_at','updated_at', 'id'])->toArray();
        foreach ($rows as $row) {
            $row = array_filter($row, function($value) { return !is_null($value) && $value !== ''; });
            $line = implode(', ', array_filter($row, function ($val) {
                return '' != $val && !is_null($val);
            }));
            $this->loadPolicyLine(trim($line), $model);
        }
        $this->setFiltered(true);
    }

    /**
     * Returns true if the loaded policy has been filtered.
     *
     * @return bool
     */
    public function isFiltered(): bool
    {
        return $this->filtered;
    }

    /**
     * Sets filtered parameter.
     *
     * @param bool $filtered
     */
    public function setFiltered(bool $filtered): void
    {
        $this->filtered = $filtered;
    }
}

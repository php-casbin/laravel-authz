<?php

declare(strict_types=1);

namespace Lauthz\Adapters;

use Lauthz\Models\Rule;
use Lauthz\Contracts\DatabaseAdapter as DatabaseAdapterContract;
use Lauthz\Contracts\BatchDatabaseAdapter as BatchDatabaseAdapterContract;
use Lauthz\Contracts\UpdatableDatabaseAdapter as UpdatableDatabaseAdapterContract; 
use Casbin\Model\Model;
use Casbin\Persist\AdapterHelper;
use DateTime;
/**
 * DatabaseAdapter.
 *
 * @author techlee@qq.com
 */
class DatabaseAdapter implements DatabaseAdapterContract, BatchDatabaseAdapterContract, UpdatableDatabaseAdapterContract
{
    use AdapterHelper;

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
        $col['p_type'] = $ptype;
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
            $temp['p_type'] = $ptype;
            $temp['created_at'] = new DateTime();
            $temp['updated_at'] = $temp['created_at'];
            foreach ($rule as $key => $value) {
                $temp['v'.strval($key)] = $value;
            }
            $cols[$i++] = $temp ?? [];
            $temp = [];
        }
        $this->eloquent->insert($cols);
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
        $count = 0;

        $instance = $this->eloquent->where('p_type', $ptype);

        foreach ($rule as $key => $value) {
            $instance->where('v'.strval($key), $value);
        }

        foreach ($instance->get() as $model) {
            if ($model->delete()) {
                ++$count;
            }
        }
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
        $count = 0;
        $instance = $this->eloquent->where('p_type', $ptype);
        foreach($rules as $rule)
        {
            foreach ($rule as $key => $value) {
                $keys[] = 'v'.strval($key);
                $con['v'.strval($key)][] = $value;
            }
        }
        $keys = array_unique($keys);
        foreach($keys as $key){
            $instance->whereIn($key, $con[$key]);
        }
        $num = $instance->delete();
        $count += $num;
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
        $count = 0;

        $instance = $this->eloquent->where('p_type', $ptype);
        foreach (range(0, 5) as $value) {
            if ($fieldIndex <= $value && $value < $fieldIndex + count($fieldValues)) {
                if ('' != $fieldValues[$value - $fieldIndex]) {
                    $instance->where('v'.strval($value), $fieldValues[$value - $fieldIndex]);
                }
            }
        }

        foreach ($instance->get() as $model) {
            if ($model->delete()) {
                ++$count;
            }
        }
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
        $instance = $this->eloquent->where('p_type', $ptype);
        foreach($oldRule as $k => $v) {
            $instance->where('v' . $k, $v);
        }
        $instance->first();
        $update = [];
        foreach($newPolicy as $k => $v) {
            $item = 'v' . $k;
            $update[$item] = $k;
        }
        $instance->update($update);
    }
}

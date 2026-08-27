<?php

namespace Lauthz\Observers;

use Lauthz\Models\Rule;

class RuleObserver
{
    public function saved(Rule $rule): void
    {
        $rule->refreshCache();
    }

    public function deleted(Rule $rule): void
    {
        $rule->refreshCache();
    }
}

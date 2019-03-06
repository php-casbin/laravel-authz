<?php

namespace Lauthz\Observers;

use Lauthz\Models\Rule;

class RuleObserver
{
    public function saved(Rule $rule)
    {
        $rule->refreshCache();
    }

    public function deleted(Rule $rule)
    {
        $rule->refreshCache();
    }
}

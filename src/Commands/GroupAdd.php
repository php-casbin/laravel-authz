<?php

namespace Lauthz\Commands;

use Illuminate\Console\Command;
use Lauthz\Facades\Enforcer;

/**
 * PolicyAdd class.
 */
class GroupAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group:add
                            {policy : the rule separated by commas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a role inheritance rule to the current policy.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $params = explode(',', $this->argument('policy'));
        array_walk($params, function (&$value) {
            $value = trim($value);
        });
        $ret = Enforcer::addGroupingPolicy(...$params);
        if ($ret) {
            $this->info('Grouping `' . implode(', ', $params) . '` created');
        } else {
            $this->error('Grouping `' . implode(', ', $params) . '` creation failed');
        }

        return $ret ? 0 : 1;
    }
}

<?php

namespace Lauthz\Commands;

use Lauthz\Facades\Enforcer;
use Illuminate\Console\Command;

/**
 * PolicyAdd class.
 */
class PolicyAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:add 
                            {policy : the rule separated by commas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds an authorization rule to the current policy';

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
        $ret = Enforcer::addPolicy(...$params);
        if ($ret) {
            $this->info('Policy `'.implode(', ', $params).'` created');
        } else {
            $this->error('Policy `'.implode(', ', $params).'` creation failed');
        }

        return $ret ? 0 : 1;
    }
}

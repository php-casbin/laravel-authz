<?php

namespace Lauthz\Commands;

use Lauthz\Facades\Enforcer;
use Illuminate\Console\Command;

/**
 * RoleAssign class.
 */
class RoleAssign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:assign
                            {user : the identifier of user}
                            {role : the name of role}
                            {--ptype= : the ptype of role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a role for a user.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = $this->argument('user');
        $role = $this->argument('role');
        $ptype = $this->option('ptype') ?: 'g';

        $ret = Enforcer::addNamedGroupingPolicy($ptype, $user, $role);
        if ($ret) {
            $this->info('Added `'.$role.'` role to `'.$user.'` successfully');
        } else {
            $this->error('Added `'.$role.'` role to `'.$user.'` failed');
        }

        return $ret ? 0 : 1;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SeedPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all system\'s permissions';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach (config('permission.system_permissions') as $group => $permissions) {
            foreach ($permissions as $permission) {
                Permission::query()->updateOrCreate(
                    ['name' => $permission, 'guard_name' => config('permission.guard')],
                    ['name' => $permission, 'guard_name' => config('permission.guard'), 'group_name' => $group],
                );
            }
        }

        $this->info('Permissions seeded successfully!');
    }
}

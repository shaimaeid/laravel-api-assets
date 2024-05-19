<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set permissions for ec2-user on /var/www/html';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Command logic goes here
        $command = 'sudo setfacl -R -m u:ec2-user:rwx /var/www/html';
        exec($command);

        $this->info('Permissions set for ec2-user on /var/www/html');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MakeApiControllerCommand extends Command
{
    protected $signature = 'make:api-controller {name}';
    protected $description = 'Create an API controller with model and requests';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = ucwords($this->argument('name'));
        
        $controllerName = "Api\\V1\\{$name}Controller";
        $modelName = $name;

        Artisan::call("make:controller", [
            'name' => $controllerName,
            '--api' => true,
            '--model' => $modelName,
            '--requests' => true,
        ]);

        Artisan::call("make:resource", [
            'name' => "Api\\V1\\$name\\{$name}Resource",
        ]);

        $this->info("API Controller {$controllerName} created successfully with model {$modelName} and requests.");
    }
}

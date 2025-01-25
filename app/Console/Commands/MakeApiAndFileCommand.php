<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class MakeApiAndFileCommand extends Command
{
    protected $signature = 'make:api-and-file {name} {version?}';

    protected $description = 'Creates an API Controller, Resource, Repository, and Service with optional versioning.';

    public function handle()
    {
        $rawName = $this->argument('name');
        $name = str_replace(' ', '', ucwords($rawName));
        $version = $this->argument('version') ?? 'V1';

        // Create API Controller and Resource
        $controllerName = "Api\\$version\\{$name}Controller";
        $modelName = $name;

        Artisan::call("make:controller", [
            'name' => $controllerName,
            '--api' => true,
            '--model' => $modelName,
            '--requests' => true,
        ]);

        Artisan::call("make:resource", [
            'name' => "Api\\$version\\$name\\{$name}Resource",
        ]);

        // Create Repository and Service
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        $servicePath = app_path("Services/{$name}Service.php");

        $repositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$name};\n\nclass {$name}Repository\n{\n    protected \${$name};\n\n    public function __construct({$name} \${$name})\n    {\n        \$this->{$name} = \${$name};\n    }\n\n    // Repository methods\n}\n";

        $serviceContent = "<?php\n\nnamespace App\\Services;\n\nuse App\\Repositories\\{$name}Repository;\n\nclass {$name}Service\n{\n    private \${$name}Repository;\n\n    public function __construct({$name}Repository \${$name}Repository)\n    {\n        \$this->{$name}Repository = \${$name}Repository;\n    }\n\n    // Service methods\n}\n";

        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }

        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        File::put($repositoryPath, $repositoryContent);
        File::put($servicePath, $serviceContent);

        // Add constructor to the Controller
        $controllerPath = app_path("Http/Controllers/Api/{$version}/{$name}Controller.php");
        if (File::exists($controllerPath)) {
            $controllerContent = File::get($controllerPath);
            $constructor = "\n    public function __construct(private {$name}Service \${$name}Service) {}\n";

            $controllerContent = preg_replace('/class .*?\n{/', "$0$constructor", $controllerContent, 1);
            File::put($controllerPath, $controllerContent);
        }

        $this->info("API Controller, Resource, Repository, and Service for {$name} created successfully in version {$version}.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeFileCommand extends Command
{
    protected $signature = 'make:file {name}';
    protected $description = 'Create files in Repository and Services folders';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');

        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        $servicePath = app_path("Services/{$name}Service.php");

        $repositoryContent = "<?php\n\nnamespace App\Repositories;\n\nclass {$name}Repository\n{\n    // Repository methods\n}\n";
        $serviceContent = "<?php\n\nnamespace App\Services;\n\nclass {$name}Service\n{\n    // Service methods\n}\n";

        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }

        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        File::put($repositoryPath, $repositoryContent);
        File::put($servicePath, $serviceContent);

        $this->info("{$name}Repository and {$name}Service created successfully.");
    }
}

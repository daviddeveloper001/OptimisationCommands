<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakeApiAndFileCommand extends Command
{
    protected $signature = 'make:api-and-file {name} {version?}';
    protected $description = 'Creates an API Controller, Resource, Repository, and Service with optional versioning.';

    public function handle()
    {
        $name = $this->formatName($this->argument('name'));
        $version = $this->argument('version') ?? 'V1';

        $this->createModelWithMigration($name);
        $this->createApiComponents($name, $version);
        $this->createRepositoryAndService($name);

        $this->info("API components for {$name} created successfully in version {$version}.");
    }

    private function formatName($rawName): string
    {
        return str_replace(' ', '', ucwords($rawName));
    }

    private function createModelWithMigration($name)
    {
        Artisan::call("make:model", ['name' => $name, '-m' => true]);
    }

    private function createApiComponents($name, $version)
    {
        $namespace = "Api\\$version\\$name";

        Artisan::call("make:controller", ['name' => "$namespace\\{$name}{$version}Controller"]);
        Artisan::call("make:request", ['name' => "$namespace\\Store{$name}{$version}Request"]);
        Artisan::call("make:request", ['name' => "$namespace\\Update{$name}{$version}Request"]);
        Artisan::call("make:resource", ['name' => "$namespace\\{$name}{$version}Resource"]);
    }

    private function createRepositoryAndService($name)
    {
        $this->ensureDirectoriesExist();
        $this->createBaseRepository();

        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        $servicePath = app_path("Services/{$name}Service.php");

        $nameMin = lcfirst($name);

        $repositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$name};\n\nclass {$name}Repository extends BaseRepository\n{\n    public function __construct({$name} \${$nameMin})\n    {\n        parent::__construct(\${$nameMin}, []);\n    }\n}\n";

        $serviceContent = "<?php\n\nnamespace App\\Services;\n\nuse App\\Repositories\\{$name}Repository;\n\nclass {$name}Service\n{\n    public function __construct(private {$name}Repository \${$nameMin}Repository) {}\n}\n";

        File::put($repositoryPath, $repositoryContent);
        File::put($servicePath, $serviceContent);
    }

    private function ensureDirectoriesExist()
    {
        foreach (['Interfaces', 'Repositories', 'Services'] as $folder) {
            if (!File::exists(app_path($folder))) {
                File::makeDirectory(app_path($folder), 0755, true);
            }
        }
    }

    private function createBaseRepository()
    {
        $interfacePath = app_path("Interfaces/BaseRepositoryInterface.php");
        $repositoryPath = app_path("Repositories/BaseRepository.php");

        if (!File::exists($interfacePath)) {
            File::put($interfacePath, "<?php\n\nnamespace App\\Interfaces;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\ninterface BaseRepositoryInterface\n{\n    public function all();\n    public function find(int \$id);\n    public function create(array \$data);\n    public function update(Model \$model, array \$data);\n    public function delete(Model \$model);\n}\n");
        }

        if (!File::exists($repositoryPath)) {
            File::put($repositoryPath, "<?php\n\nnamespace App\\Repositories;\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse App\\Interfaces\\BaseRepositoryInterface;\n\nclass BaseRepository implements BaseRepositoryInterface\n{\n    protected Model \$model;\n    \n    public function __construct(Model \$model)\n    {\n        \$this->model = \$model;\n    }\n\n    public function all() { return \$this->model->all(); }\n    public function find(int \$id) { return \$this->model->find(\$id); }\n    public function create(array \$data) { return \$this->model->create(\$data); }\n    public function update(Model \$model, array \$data) { \$model->update(\$data); return \$model; }\n    public function delete(Model \$model) { return \$model->delete(); }\n}\n");
        }
    }
}

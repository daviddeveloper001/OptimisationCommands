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
        $rawName = $this->argument('name');
        $name = str_replace(' ', '', ucwords($rawName));
        $version = $this->argument('version') ?? 'V1';

        $this->createModelWithMigration($name);
        $this->createApiComponents($name, $version);
        $this->createBaseRepository();
        $this->createRepository($name);
        $this->createService($name, $version);
        $this->updateController($name, $version);

        $this->info("API components for {$name} created successfully in version {$version}.");
    }

    private function createModelWithMigration(string $name): void
    {
        Artisan::call("make:model", [
            'name' => $name,
            '-m' => true,
        ]);
    }

    private function createApiComponents(string $name, string $version): void
    {
        $versionSuffix = $version !== 'V1' ? $version : '';
        $controllerName = "Api\\$version\\{$name}Controller{$versionSuffix}";

        Artisan::call("make:controller", [
            'name' => $controllerName,
        ]);

        Artisan::call("make:request", [
            'name' => "Api\\$version\\$name\\Store{$name}Request{$versionSuffix}",
        ]);

        Artisan::call("make:request", [
            'name' => "Api\\$version\\$name\\Update{$name}Request{$versionSuffix}",
        ]);

        Artisan::call("make:resource", [
            'name' => "Api\\$version\\$name\\{$name}Resource{$versionSuffix}",
        ]);
    }

    private function createBaseRepository(): void
    {
        $interfacePath = app_path("Interfaces/BaseRepositoryInterface.php");
        $baseRepositoryPath = app_path("Repositories/BaseRepository.php");

        if (!File::exists(app_path('Interfaces'))) {
            File::makeDirectory(app_path('Interfaces'), 0755, true);
        }

        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }

        if (!File::exists($interfacePath)) {
            $interfaceContent = "<?php\n\nnamespace App\\Interfaces;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\ninterface BaseRepositoryInterface\n{\n    public function all();\n    public function find(Model \$model);\n    public function findBy(int \$id);\n    public function create(array \$data);\n    public function update(Model \$model, array \$data);\n    public function delete(Model \$model);\n}\n";

            File::put($interfacePath, $interfaceContent);
        }

        if (!File::exists($baseRepositoryPath)) {
            $baseRepositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse App\\Interfaces\\BaseRepositoryInterface;\n\nclass BaseRepository implements BaseRepositoryInterface\n{\n    protected \$model;\n    protected \$relations = [];\n\n    public function __construct(Model \$model, array \$relations = [])\n    {\n        \$this->model = \$model;\n        \$this->relations = \$relations;\n    }\n\n    public function all()\n    {\n        \$query = \$this->model->latest();\n        if (!empty(\$this->relations)) {\n            \$query->with(\$this->relations);\n        }\n        return \$query->get();\n    }\n\n    public function find(Model \$model)\n    {\n        \$query = \$this->model;\n        if (!empty(\$this->relations)) {\n            \$query->with(\$this->relations);\n        }\n        return \$query->find(\$model);\n    }\n\n    public function create(array \$data)\n    {\n        return \$this->model->create(\$data);\n    }\n\n    public function update(Model \$model, array \$data)\n    {\n        \$model->fill(\$data);\n        \$model->save();\n        return \$model;\n    }\n\n    public function delete(Model \$model)\n    {\n        return \$model->delete();\n    }\n\n    public function findBy(int \$id)\n    {\n        return \$this->model->find(\$id);\n    }\n}\n";

            File::put($baseRepositoryPath, $baseRepositoryContent);
        }
    }

    private function createRepository(string $name): void
    {
        $repositoryPath = app_path("Repositories/{$name}Repository.php");

        if (!File::exists($repositoryPath)) {
            $nameMin = lcfirst($name);
            $repositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$name};\n\nclass {$name}Repository extends BaseRepository\n{\n    const RELATIONS = [];\n\n    public function __construct({$name} \${$nameMin})\n    {\n        parent::__construct(\${$nameMin}, self::RELATIONS);\n    }\n}\n";

            File::put($repositoryPath, $repositoryContent);
        }
    }

    private function createService(string $name, string $version): void
    {
        $versionSuffix = $version !== 'V1' ? $version : '';
        $servicePath = app_path("Services/{$name}Service{$versionSuffix}.php");

        if (!File::exists($servicePath)) {
            $nameMin = lcfirst($name);
            $serviceContent = "<?php\n\nnamespace App\\Services;\n\nuse App\\Models\\{$name};\nuse App\\Repositories\\{$name}Repository;\n\nclass {$name}Service{$versionSuffix}\n{\n    public function __construct(private {$name}Repository \${$nameMin}Repository) {}\n\n    public function getAll{$name}s()\n    {\n        return \$this->{$nameMin}Repository->all();\n    }\n\n    public function get{$name}ById({$name} \${$nameMin})\n    {\n        return \$this->{$nameMin}Repository->find(\${$nameMin});\n    }\n\n    public function create{$name}(array \$data)\n    {\n        return \$this->{$nameMin}Repository->create(\$data);\n    }\n\n    public function update{$name}({$name} \${$nameMin}, array \$data)\n    {\n        return \$this->{$nameMin}Repository->update(\${$nameMin}, \$data);\n    }\n\n    public function delete{$name}({$name} \${$nameMin})\n    {\n        return \$this->{$nameMin}Repository->delete(\${$nameMin});\n    }\n}\n";

            File::put($servicePath, $serviceContent);
        }
    }

    private function updateController(string $name, string $version): void
    {
        $versionSuffix = $version !== 'V1' ? $version : '';
        $controllerPath = app_path("Http/Controllers/Api/{$version}/{$name}Controller{$versionSuffix}.php");
    
        if (File::exists($controllerPath)) {
            $controllerContent = File::get($controllerPath);
    
            $nameMin = lcfirst($name);
            $trait = "use App\Traits\ApiResponses;";
            $constructor = "\n    public function __construct(private {$name}Service{$versionSuffix} \${$nameMin}Service) {}\n";
    
            $methods = <<<METHODS
    
        public function index()
        {
            try {
                \${$nameMin}s = \$this->{$nameMin}Service->getAll{$name}s();
                return \$this->ok('{$name}s retrieved successfully', {$name}Resource::collection(\${$nameMin}s));
            } catch (Exception \$e) {
                return \$this->error('Failed to retrieve {$name}s', 500);
            }
        }
    
        public function store(Store{$name}Request{$versionSuffix} \$request)
        {
            try {
                \${$nameMin} = \$this->{$nameMin}Service->create{$name}(\$request->validated());
                return \$this->ok('{$name} created successfully', new {$name}Resource(\${$nameMin}));
            } catch (Exception \$e) {
                return \$this->error('Failed to create {$name}', 500);
            }
        }
    
        public function show({$name} \${$nameMin})
        {
            try {
                return \$this->ok('{$name} retrieved successfully', new {$name}Resource(\${$nameMin}));
            } catch (Exception \$e) {
                return \$this->error('Failed to retrieve {$name}', 500);
            }
        }
    
        public function update(Update{$name}Request{$versionSuffix} \$request, {$name} \${$nameMin})
        {
            try {
                \$this->{$nameMin}Service->update{$name}(\${$nameMin}, \$request->validated());
                return \$this->ok('{$name} updated successfully');
            } catch (Exception \$e) {
                return \$this->error('Failed to update {$name}', 500);
            }
        }
    
        public function destroy({$name} \${$nameMin})
        {
            try {
                \$this->{$nameMin}Service->delete{$name}(\${$nameMin});
                return \$this->ok('{$name} deleted successfully');
            } catch (Exception \$e) {
                return \$this->error('Failed to delete {$name}', 500);
            }
        }
    
    METHODS;
    
            // Add the trait if not already present
            if (!str_contains($controllerContent, $trait)) {
                $controllerContent = preg_replace('/namespace .*?;/', "$0\n\n$trait", $controllerContent, 1);
            }
    
            // Add the constructor and methods
            $controllerContent = preg_replace('/class .*?\n{/', "$0$constructor$methods", $controllerContent, 1);
    
            File::put($controllerPath, $controllerContent);
        }
    }
}
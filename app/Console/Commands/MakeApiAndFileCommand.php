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

        // Create API Controller and Resource
        $controllerName = "Api\\$version\\{$name}Controller";


        Artisan::call("make:controller", [
            'name' => $controllerName,
        ]);

        Artisan::call("make:request", [
            'name' => "Api\\$version\\$name\\Store{$name}Request",
        ]);


        Artisan::call("make:request", [
            'name' => "Api\\$version\\$name\\Update{$name}Request",
        ]);


        Artisan::call("make:resource", [
            'name' => "Api\\$version\\$name\\{$name}Resource",
        ]);

        // Create Interface and BaseRepository
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

        // Create Repository and Service
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        $servicePath = app_path("Services/{$name}Service.php");

        $repositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$name};\n\nclass {$name}Repository extends BaseRepository\n{\n    const RELATIONS = [];\n\n    public function __construct({$name} \${$name})\n    {\n        parent::__construct(\${$name}, self::RELATIONS);\n    }\n}\n";

        $serviceContent = "<?php\n\nnamespace App\\Services;\n\nuse App\\Models\\{$name};\nuse App\\Repositories\\{$name}Repository;\n\nclass {$name}Service\n{\n    public function __construct(private {$name}Repository \${$name}Repository) {}\n\n    public function getAll{$name}s()\n    {\n        return \$this->{$name}Repository->all();\n    }\n\n    public function get{$name}ById({$name} \${$name})\n    {\n        return \$this->{$name}Repository->find(\${$name});\n    }\n\n    public function create{$name}(array \$data)\n    {\n        return \$this->{$name}Repository->create(\$data);\n    }\n\n    public function update{$name}({$name} \${$name}, array \$data)\n    {\n        return \$this->{$name}Repository->update(\${$name}, \$data);\n    }\n\n    public function delete{$name}({$name} \${$name})\n    {\n        return \$this->{$name}Repository->delete(\${$name});\n    }\n}\n";

        if (!File::exists($repositoryPath)) {
            File::put($repositoryPath, $repositoryContent);
        }

        if (!File::exists($servicePath)) {
            File::put($servicePath, $serviceContent);
        }

        $controllerPath = app_path("Http/Controllers/Api/{$version}/{$name}Controller.php");
        if (File::exists($controllerPath)) {
            $controllerContent = File::get($controllerPath);

            // Agregar el constructor
            $constructor = "\n    public function __construct(private {$name}Service \${$name}Service) {}\n";

            // Agregar los métodos
            $methods = "\n    public function index()\n    {\n        \${$name}s = \$this->{$name}Service->getAll{$name}s();\n        return {$name}Resource::collection(\${$name}s);\n    }\n\n    public function store(Store{$name}Request \$request)\n    {\n        \${$name} = \$this->{$name}Service->create{$name}(\$request->validated());\n        return response()->json([\n            'message' => '{$name} created successfully',\n            '{$name}' => \${$name},\n        ]);\n    }\n\n    public function show({$name} \${$name})\n    {\n        return new {$name}Resource(\${$name});\n    }\n\n    public function update(Update{$name}Request \$request, {$name} \${$name})\n    {\n        \$this->{$name}Service->update{$name}(\${$name}, \$request->validated());\n        return response()->json([\n            'message' => '{$name} updated successfully',\n        ]);\n    }\n\n    public function destroy({$name} \${$name})\n    {\n        \$this->{$name}Service->delete{$name}(\${$name});\n        return response()->json([\n            'message' => '{$name} deleted successfully',\n        ]);\n    }\n";

            // Insertar el constructor y los métodos en el contenido del controlador
            $controllerContent = preg_replace('/class .*?\n{/', "$0$constructor$methods", $controllerContent, 1);
            File::put($controllerPath, $controllerContent);
        }


        $this->info("API Controller, Resource, Repository, Service, and Base Repository for {$name} created successfully in version {$version}.");
    }
}

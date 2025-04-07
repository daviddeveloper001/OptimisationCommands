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
        $this->createApiController();
        $this->createBaseRepository();
        $this->createRepository($name);
        $this->createService($name, $version);
        $this->createFilter($name);
        $this->createQueryFilter();
        $this->createException($name);
        $this->createBaseModel($name);
        $this->updateRoutes($name, $version);
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

    private function createApiController(): void
    {
        $apiControllerPath = app_path("Http/Controllers/Api/V1/ApiController.php");

        // Asegúrate de que la carpeta exista
        if (!File::exists(app_path("Http/Controllers/Api/V1"))) {
            File::makeDirectory(app_path("Http/Controllers/Api/V1"), 0755, true);
        }

        if (!File::exists($apiControllerPath)) {
            $apiControllerContent = <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;

class ApiController extends Controller
{
    use ApiResponses;

    public function include(string \$relationship): bool
    {
        \$param = request()->get('include');

        if (!isset(\$param)) {
            return false;
        }

        \$includesValues = explode(',', strtolower(\$param));

        return in_array(strtolower(\$relationship), \$includesValues);
    }
}
PHP;

            File::put($apiControllerPath, $apiControllerContent);
        }
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
        $servicePath = app_path("Services/Api/{$version}/{$name}Service{$versionSuffix}.php");

        // Asegúrate de que la carpeta exista
        if (!File::exists(app_path("Services/Api/{$version}"))) {
            File::makeDirectory(app_path("Services/Api/{$version}"), 0755, true);
        }

        if (!File::exists($servicePath)) {
            $nameMin = lcfirst($name);
            $serviceContent = <<<PHP
    <?php
    
    namespace App\Services\Api\\{$version};
    
    use App\Models\\{$name};
    use App\Exceptions\\{$name}Exception;
    use App\Repositories\\{$name}Repository;
    use Illuminate\Http\Response;
    
    class {$name}Service{$versionSuffix}
    {
        public function __construct(private {$name}Repository \${$nameMin}Repository) {}
    
        public function getAll{$name}s(\$filters, \$perPage)
        {
            try {
                return {$name}::filter(\$filters)->paginate(\$perPage);
            } catch (\Exception \$e) {
                throw new {$name}Exception('Failed to retrieve {$name}s', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        public function get{$name}ById({$name} \${$nameMin})
        {
            \$result = \$this->{$nameMin}Repository->find(\${$nameMin});
            if (!\$result) {
                throw new {$name}Exception('{$name} not found', Response::HTTP_NOT_FOUND);
            }
            return \$result;
        }
    
        public function create{$name}(array \$data)
        {
            try {
                return \$this->{$nameMin}Repository->create(\$data);
            } catch (\Exception \$e) {
                throw new {$name}Exception('Failed to create {$name}', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        public function update{$name}({$name} \${$nameMin}, array \$data)
        {
            try {
                return \$this->{$nameMin}Repository->update(\${$nameMin}, \$data);
            } catch (\Exception \$e) {
                throw new {$name}Exception('Failed to update {$name}', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        public function delete{$name}({$name} \${$nameMin})
        {
            try {
                return \$this->{$nameMin}Repository->delete(\${$nameMin});
            } catch (\Exception \$e) {
                throw new {$name}Exception('Failed to delete {$name}', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
    PHP;

            File::put($servicePath, $serviceContent);
        }
    }

    private function createFilter(string $name): void
    {
        $filterPath = app_path("Filters/{$name}Filter.php");

        // Asegúrate de que la carpeta exista
        if (!File::exists(app_path('Filters'))) {
            File::makeDirectory(app_path('Filters'), 0755, true);
        }

        if (!File::exists($filterPath)) {
            $filterContent = <<<PHP
<?php

namespace App\Filters;

class {$name}Filter extends QueryFilter
{
    protected array \$sortable = [
        'name',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
    ];

    public function name(string \$value): void
    {
        \$this->builder->where('name', 'LIKE', "%\$value%");
    }

    public function createdAt(string \$value): void
    {
        \$dates = explode(',', \$value);

        if (count(\$dates) > 1) {
            \$this->builder->whereBetween('created_at', \$dates);
        } else {
            \$this->builder->whereDate('created_at', \$value);
        }
    }

    public function updatedAt(string \$value): void
    {
        \$dates = explode(',', \$value);

        if (count(\$dates) > 1) {
            \$this->builder->whereBetween('updated_at', \$dates);
        } else {
            \$this->builder->whereDate('updated_at', \$value);
        }
    }

    public function include(string \$value): void
    {
        \$this->builder->with(explode(',', \$value));
    }
}
PHP;

            File::put($filterPath, $filterContent);
        }
    }

    private function createQueryFilter(): void
    {
        $queryFilterPath = app_path("Filters/QueryFilter.php");

        // Asegúrate de que la carpeta exista
        if (!File::exists(app_path('Filters'))) {
            File::makeDirectory(app_path('Filters'), 0755, true);
        }

        if (!File::exists($queryFilterPath)) {
            $queryFilterContent = <<<PHP
<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Builder \$builder;
    protected Request \$request;
    protected array \$sortable = [];

    public function __construct(Request \$request)
    {
        \$this->request = \$request;
    }

    public function apply(Builder \$builder): Builder
    {
        \$this->builder = \$builder;

        foreach (\$this->request->all() as \$key => \$value) {
            if (method_exists(\$this, \$key)) {
                \$this->\$key(\$value);
            }
        }

        return \$builder;
    }

    protected function filter(array \$arr): Builder
    {
        foreach (\$arr as \$key => \$value) {
            if (method_exists(\$this, \$key)) {
                \$this->\$key(\$value);
            }
        }

        return \$this->builder;
    }

    protected function sort(string \$value): void
    {
        \$sortAttributes = explode(',', \$value);

        foreach (\$sortAttributes as \$sortAttribute) {
            \$direction = 'asc';

            if (strpos(\$sortAttribute, '-') === 0) {
                \$direction = 'desc';
                \$sortAttribute = substr(\$sortAttribute, 1);
            }

            if (!in_array(\$sortAttribute, \$this->sortable) && !array_key_exists(\$sortAttribute, \$this->sortable)) {
                continue;
            }

            \$columnName = \$this->sortable[\$sortAttribute] ?? \$sortAttribute;

            \$this->builder->orderBy(\$columnName, \$direction);
        }
    }
}
PHP;

            File::put($queryFilterPath, $queryFilterContent);
        }
    }

    private function createException(string $name): void
    {
        $exceptionPath = app_path("Exceptions/{$name}Exception.php");

        // Asegúrate de que la carpeta exista
        if (!File::exists(app_path('Exceptions'))) {
            File::makeDirectory(app_path('Exceptions'), 0755, true);
        }

        if (!File::exists($exceptionPath)) {
            $exceptionContent = <<<PHP
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class {$name}Exception extends Exception
{
    public function __construct(
        string \$message = '{$name} error occurred',
        int \$code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Exception \$previous = null
    ) {
        parent::__construct(\$message, \$code, \$previous);
    }

    public function render(\$request)
    {
        if (\$request->isJson()) {
            return response()->json([
                'message' => \$this->getMessage(),
            ], \$this->code);
        }
    }
}
PHP;

            File::put($exceptionPath, $exceptionContent);
        }
    }

    private function createBaseModel(string $name): void
    {
        $modelPath = app_path("Models/{$name}.php");

        if (File::exists($modelPath)) {
            $modelContent = File::get($modelPath);

            if (!str_contains($modelContent, 'SoftDeletes')) {
                $modelContent = preg_replace('/class .*? extends Model/', "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n\n$0\n{\n    use SoftDeletes;", $modelContent);
            }

            if (!str_contains($modelContent, 'scopeFilter')) {
                $scopeMethod = <<<PHP

    public function scopeFilter(Builder \$builder, QueryFilter \$filters): Builder
    {
        return \$filters->apply(\$builder);
    }
PHP;

                $modelContent = preg_replace('/\}\s*$/', "$scopeMethod\n}", $modelContent);
            }

            File::put($modelPath, $modelContent);
        }
    }

    private function updateRoutes(string $name, string $version): void
    {
        $routesPath = base_path("routes/api_v2.php");
        $routeName = strtolower(str_replace('_', '-', $name));
        $controllerName = "App\\Http\\Controllers\\Api\\$version\\{$name}Controller$version";

        if (File::exists($routesPath)) {
            $routesContent = File::get($routesPath);

            if (!str_contains($routesContent, $routeName)) {
                $newRoute = "Route::apiResource('{$routeName}s', {$controllerName}::class);";
                $routesContent = preg_replace('/\}\);/', "    $newRoute\n});", $routesContent);
                File::put($routesPath, $routesContent);
            }
        }
    }

    private function updateController(string $name, string $version): void
{
    $versionSuffix = $version !== 'V1' ? $version : '';
    $controllerPath = app_path("Http/Controllers/Api/{$version}/{$name}Controller{$versionSuffix}.php");

    $nameMin = lcfirst($name);
    $controllerContent = <<<PHP
<?php

namespace App\Http\Controllers\Api\\{$version};

use App\Models\\{$name};
use App\Filters\\{$name}SFilter;
use App\Services\\{$name}Service{$versionSuffix};
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\\{$version}\\{$name}\\{$name}Resource{$versionSuffix};
use App\Http\Requests\Api\\{$version}\\{$name}\\Store{$name}Request{$versionSuffix};
use App\Http\Requests\Api\\{$version}\\{$name}\\Update{$name}Request{$versionSuffix};

class {$name}Controller{$versionSuffix} extends ApiController
{
    public function __construct(private {$name}Service{$versionSuffix} \${$nameMin}Service) {}

    public function index({$name}SFilter \$filters)
    {
        \$perPage = request()->input('per_page', 10);

        \${$nameMin}s = \$this->{$nameMin}Service->getAll{$name}s(\$filters, \$perPage);

        return \$this->ok('{$name}s retrieved successfully', {$name}Resource{$versionSuffix}::collection(\${$nameMin}s));
    }

    public function store(Store{$name}Request{$versionSuffix} \$request)
    {
        \${$nameMin} = \$this->{$nameMin}Service->create{$name}(\$request->validated());
        return \$this->ok('{$name} created successfully', new {$name}Resource{$versionSuffix}(\${$nameMin}));
    }

    public function show({$name} \${$nameMin})
    {
        return \$this->ok('{$name} retrieved successfully', new {$name}Resource{$versionSuffix}(\${$nameMin}));
    }

    public function update(Update{$name}Request{$versionSuffix} \$request, {$name} \${$nameMin})
    {
        \$this->{$nameMin}Service->update{$name}(\${$nameMin}, \$request->validated());
        return \$this->ok('{$name} updated successfully');
    }

    public function destroy({$name} \${$nameMin})
    {
        \$this->{$nameMin}Service->delete{$name}(\${$nameMin});
        return \$this->ok('{$name} deleted successfully');
    }
}
PHP;

    // Sobrescribe el archivo del controlador con el contenido actualizado
    File::put($controllerPath, $controllerContent);
}
}

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

        $this->createModelBase();
        $this->createModelWithMigration($name);
        $this->createApiComponents($name, $version);
        $this->createApiResponsesTrait();
        $this->createApiController();
        $this->createBaseRepository();
        $this->createRepository($name);
        $this->createService($name, $version);
        $this->createFilter($name);
        $this->createQueryFilter();
        $this->createApiRenderableExceptionInterface($version);
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

    private function createApiComponents(string $name, ?string $version = null): void
    {
        $version = $version ?? 'V1';
        $versionSuffix = $version; // Siempre agregar el sufijo de versión

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

    private function createApiResponsesTrait(): void
    {
        $traitDir = app_path('Traits');
        $traitPath = "{$traitDir}/ApiResponses.php";

        if (!File::exists($traitDir)) {
            File::makeDirectory($traitDir, 0755, true);
        }

        if (!File::exists($traitPath)) {
            $traitContent = <<<PHP
<?php

namespace App\Traits;

trait ApiResponses
{
    protected function ok(\$message, \$data = [])
    {
        return \$this->success(\$message, \$data, 200);
    }

    protected function success(\$message, \$data = [], \$statusCode = 200)
    {
        return response()->json([
            'data' => \$data,
            'message' => \$message,
            'status' => \$statusCode
        ], \$statusCode);
    }

    protected function error(\$message, \$statusCode)
    {
        return response()->json([
            'message' => \$message,
            'status' => \$statusCode
        ], \$statusCode);
    }
}
PHP;

            File::put($traitPath, $traitContent);
            $this->info('ApiResponses trait created successfully.');
        }
    }


    private function createApiController(string $version = 'V1'): void
    {
        $versionSuffix = $version;
        $versionNamespace = $version;
        $directoryPath = app_path("Http/Controllers/Api/{$versionNamespace}");
        $controllerFileName = "ApiController{$versionSuffix}.php";
        $apiControllerPath = "{$directoryPath}/{$controllerFileName}";

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        if (!File::exists($apiControllerPath)) {
            $apiControllerContent = <<<PHP
<?php

namespace App\Http\Controllers\Api\\{$versionNamespace};

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Interfaces\\{$versionSuffix}\\ApiRenderableException{$versionSuffix};
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiController{$versionSuffix} extends Controller
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

    protected function handleException(Throwable \$e)
    {
        if (\$e instanceof ApiRenderableException{$versionSuffix}) {
            Log::error(get_class(\$e) . ': ' . \$e->getUserMessage(), [
                'developer_hint' => \$e->getDeveloperHint(),
                'exception' => \$e,
            ]);

            return response()->json([
                'message' => \$e->getUserMessage(),
                'error_code' => \$e->getStatusCode(),
            ], \$e->getStatusCode());
        }

        Log::error("Unhandled Exception", ['exception' => \$e]);

        return response()->json([
            'message' => 'An unexpected error occurred',
        ], 500);
    }
}
PHP;

            File::put($apiControllerPath, $apiControllerContent);
        }
    }




    private function createBaseRepository(string $version = 'V1'): void
    {
        $versionSuffix = $version;
        $interfaceDir = app_path("Interfaces/{$versionSuffix}");
        $repositoryDir = app_path("Repositories/{$versionSuffix}");

        $interfacePath = "{$interfaceDir}/BaseRepositoryInterface{$versionSuffix}.php";
        $repositoryPath = "{$repositoryDir}/BaseRepository{$versionSuffix}.php";

        // Crear carpetas si no existen
        if (!File::exists($interfaceDir)) {
            File::makeDirectory($interfaceDir, 0755, true);
        }

        if (!File::exists($repositoryDir)) {
            File::makeDirectory($repositoryDir, 0755, true);
        }

        // Crear interface
        if (!File::exists($interfacePath)) {
            $interfaceContent = <<<PHP
<?php

namespace App\Interfaces\\{$versionSuffix};

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface{$versionSuffix}
{
    public function all();
    public function find(Model \$model);
    public function findBy(int \$id);
    public function create(array \$data);
    public function update(Model \$model, array \$data);
    public function delete(Model \$model);
}
PHP;

            File::put($interfacePath, $interfaceContent);
        }

        // Crear repositorio base
        if (!File::exists($repositoryPath)) {
            $repositoryContent = <<<PHP
<?php

namespace App\Repositories\\{$versionSuffix};

use Illuminate\Database\Eloquent\Model;
use App\Interfaces\\{$versionSuffix}\BaseRepositoryInterface{$versionSuffix};

class BaseRepository{$versionSuffix} implements BaseRepositoryInterface{$versionSuffix}
{
    protected \$model;
    protected \$relations = [];

    public function __construct(Model \$model, array \$relations = [])
    {
        \$this->model = \$model;
        \$this->relations = \$relations;
    }

    public function all()
    {
        \$query = \$this->model->latest();
        if (!empty(\$this->relations)) {
            \$query->with(\$this->relations);
        }
        return \$query->get();
    }

    public function find(Model \$model)
    {
        \$query = \$this->model;
        if (!empty(\$this->relations)) {
            \$query->with(\$this->relations);
        }
        return \$query->find(\$model);
    }

    public function create(array \$data)
    {
        return \$this->model->create(\$data);
    }

    public function update(Model \$model, array \$data)
    {
        \$model->fill(\$data);
        \$model->save();
        return \$model;
    }

    public function delete(Model \$model)
    {
        return \$model->delete();
    }

    public function findBy(int \$id)
    {
        return \$this->model->find(\$id);
    }
}
PHP;

            File::put($repositoryPath, $repositoryContent);
        }
    }


    private function createRepository(string $name, string $version = 'V1'): void
    {
        $versionSuffix = $version;
        $classSuffix = $versionSuffix; // Para el sufijo de clase
        $dir = app_path("Repositories/{$versionSuffix}");
        $repositoryPath = "{$dir}/{$name}Repository{$classSuffix}.php";

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (!File::exists($repositoryPath)) {
            $nameMin = lcfirst($name);

            $repositoryContent = <<<PHP
<?php

namespace App\Repositories\\{$versionSuffix};

use App\Models\\{$name};
use App\Repositories\\{$versionSuffix}\BaseRepository{$classSuffix};

class {$name}Repository{$classSuffix} extends BaseRepository{$classSuffix}
{
    const RELATIONS = [];

    public function __construct({$name} \${$nameMin})
    {
        parent::__construct(\${$nameMin}, self::RELATIONS);
    }
}
PHP;

            File::put($repositoryPath, $repositoryContent);
        }
    }


    private function createService(string $name, string $version = 'V1'): void
    {
        $versionSuffix = $version;
        $dir = app_path("Services/Api/{$versionSuffix}");
        $servicePath = "{$dir}/{$name}Service{$versionSuffix}.php";

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (!File::exists($servicePath)) {
            $nameMin = lcfirst($name);

            $serviceContent = <<<PHP
<?php

namespace App\Services\Api\\{$versionSuffix};

use App\Models\\{$name};
use App\Exceptions\\{$name}Exception;
use App\Repositories\\{$versionSuffix}\\{$name}Repository{$versionSuffix};
use Illuminate\Http\Response;

class {$name}Service{$versionSuffix}
{
    public function __construct(private {$name}Repository{$versionSuffix} \${$nameMin}Repository) {}

    public function getAll{$name}s(\$filters, \$perPage)
    {
        try {
            return {$name}::filter(\$filters)->paginate(\$perPage);
        } catch (\Exception \$e) {
            throw new {$name}Exception(
                'Failed to retrieve {$name}s',
                developerHint: \$e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: \$e
            );
        }
    }

    public function get{$name}ById({$name} \${$nameMin})
    {
        try {
            \$result = \$this->{$nameMin}Repository->find(\${$nameMin});
            if (!\$result) {
                throw new {$name}Exception('{$name} not found', Response::HTTP_NOT_FOUND);
            }
            return \$result;
        } catch (\Exception \$e) {
            throw new {$name}Exception(
                'Failed to retrieve {$name}',
                developerHint: \$e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: \$e
            );
        }
    }

    public function create{$name}(array \$data)
    {
        try {
            return \$this->{$nameMin}Repository->create(\$data);
        } catch (\Exception \$e) {
            throw new {$name}Exception(
                'Failed to create {$name}',
                developerHint: \$e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: \$e
            );
        }
    }

    public function update{$name}({$name} \${$nameMin}, array \$data)
    {
        try {
            return \$this->{$nameMin}Repository->update(\${$nameMin}, \$data);
        } catch (\Exception \$e) {
            throw new {$name}Exception(
                'Failed to update {$name}',
                developerHint: \$e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: \$e
            );
        }
    }

    public function delete{$name}({$name} \${$nameMin})
    {
        try {
            return \$this->{$nameMin}Repository->delete(\${$nameMin});
        } catch (\Exception \$e) {
            throw new {$name}Exception(
                'Failed to delete {$name}',
                developerHint: \$e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: \$e
            );
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

    private function createApiRenderableExceptionInterface(string $version = 'V1'): void
    {
        $versionSuffix = $version;
        $interfaceDir = app_path("Interfaces/{$versionSuffix}");
        $interfacePath = "{$interfaceDir}/ApiRenderableException{$versionSuffix}.php";

        if (!File::exists($interfaceDir)) {
            File::makeDirectory($interfaceDir, 0755, true);
        }

        if (!File::exists($interfacePath)) {
            $interfaceContent = <<<PHP
<?php

namespace App\Interfaces\\{$versionSuffix};

interface ApiRenderableException{$versionSuffix}
{
    public function getStatusCode(): int;
    public function getUserMessage(): string;
    public function getDeveloperHint(): ?string;
}
PHP;

            File::put($interfacePath, $interfaceContent);
            $this->info("ApiRenderableException{$versionSuffix} interface created successfully.");
        }
    }


    private function createException(string $name): void
    {
        $exceptionDir = app_path('Exceptions');
        $exceptionPath = "{$exceptionDir}/{$name}Exception.php";

        if (!File::exists($exceptionDir)) {
            File::makeDirectory($exceptionDir, 0755, true);
        }

        if (!File::exists($exceptionPath)) {
            $exceptionContent = <<<PHP
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use App\Interfaces\V1\ApiRenderableExceptionV1;

class {$name}Exception extends Exception implements ApiRenderableExceptionV1
{
    private ?string \$developerHint;

    public function __construct(
        string \$message = '{$name} error occurred',
        ?string \$developerHint = null,
        int \$code = Response::HTTP_BAD_REQUEST,
        ?Exception \$previous = null
    ) {
        parent::__construct(\$message, \$code, \$previous);
        \$this->developerHint = \$developerHint;
    }

    public function getStatusCode(): int
    {
        return \$this->getCode();
    }

    public function getUserMessage(): string
    {
        return \$this->getMessage();
    }

    public function getDeveloperHint(): ?string
    {
        return \$this->developerHint;
    }
}
PHP;

            File::put($exceptionPath, $exceptionContent);
        }
    }

    private function createModelBase(): void
    {
        $modelBasePath = app_path("Models/ModelBase.php");

        if (!File::exists(app_path('Models'))) {
            File::makeDirectory(app_path('Models'), 0755, true);
        }

        if (!File::exists($modelBasePath)) {
            $modelBaseContent = <<<PHP
<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelBase extends Model
{
    use SoftDeletes;
    use HasFactory;

    public function scopeFilter(Builder \$builder, QueryFilter \$filters): Builder
    {
        return \$filters->apply(\$builder);
    }
}
PHP;

            File::put($modelBasePath, $modelBaseContent);
            $this->info('ModelBase created successfully.');
        }
    }




    private function createBaseModel(string $name): void
    {
        $modelPath = app_path("Models/{$name}.php");

        // Si el archivo no existe, crearlo con la estructura base deseada
        if (!File::exists($modelPath)) {
            $modelContent = <<<PHP
<?php

namespace App\Models;


class {$name} extends ModelBase
{
    protected \$table = '';

    protected \$fillable = [
        
    ];

    protected \$casts = [
        
    ];
}
PHP;

            File::put($modelPath, $modelContent);
            return; // ya quedó con la estructura deseada, no seguir procesando
        }

        // Si ya existe, modificarlo si hace falta
        $modelContent = File::get($modelPath);



        if (File::exists($modelPath)) {
            $modelContent = <<<PHP
<?php

namespace App\Models;

class {$name} extends ModelBase
{
    protected \$table = '';

    protected \$fillable = [
        
    ];

    protected \$casts = [
        
    ];
}
PHP;

            File::put($modelPath, $modelContent);
            return; // ya quedó con la estructura deseada, no seguir procesando
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
    private function updateController(string $name, ?string $version = null): void
    {
        $version = $version ?? 'V1';
        $versionSuffix = $version;
        $controllerPath = app_path("Http/Controllers/Api/{$version}/{$name}Controller{$versionSuffix}.php");

        $nameMin = lcfirst($name);

        $controllerContent = <<<PHP
<?php

namespace App\Http\Controllers\Api\\{$version};

use App\Models\\{$name};
use App\Filters\\{$name}Filter;
use App\Services\Api\\{$version}\\{$name}Service{$versionSuffix};
use App\Http\Controllers\Api\\{$version}\\ApiController{$versionSuffix};
use App\Http\Resources\Api\\{$version}\\{$name}\\{$name}Resource{$versionSuffix};
use App\Http\Requests\Api\\{$version}\\{$name}\\Store{$name}Request{$versionSuffix};
use App\Http\Requests\Api\\{$version}\\{$name}\\Update{$name}Request{$versionSuffix};

class {$name}Controller{$versionSuffix} extends ApiController{$versionSuffix}
{
    public function __construct(private {$name}Service{$versionSuffix} \${$nameMin}Service) {}

    public function index({$name}Filter \$filters)
    {
        try {
            \$perPage = request()->input('per_page', 10);
            \${$nameMin}s = \$this->{$nameMin}Service->getAll{$name}s(\$filters, \$perPage);

            return \$this->ok('{$name}s retrieved successfully', {$name}Resource{$versionSuffix}::collection(\${$nameMin}s));
        } catch (\\Throwable \$e) {
            return \$this->handleException(\$e);
        }
    }

    public function store(Store{$name}Request{$versionSuffix} \$request)
    {
        try {
            \${$nameMin} = \$this->{$nameMin}Service->create{$name}(\$request->validated());
            return \$this->ok('{$name} created successfully', new {$name}Resource{$versionSuffix}(\${$nameMin}));
        } catch (\\Throwable \$e) {
            return \$this->handleException(\$e);
        }
    }

    public function show({$name} \${$nameMin})
    {
        try {
            return \$this->ok('{$name} retrieved successfully', new {$name}Resource{$versionSuffix}(\${$nameMin}));
        } catch (\\Throwable \$e) {
            return \$this->handleException(\$e);
        }
    }

    public function update(Update{$name}Request{$versionSuffix} \$request, {$name} \${$nameMin})
    {
        try {
            \${$nameMin} = \$this->{$nameMin}Service->update{$name}(\${$nameMin}, \$request->validated());
            return \$this->ok('{$name} updated successfully', new {$name}Resource{$versionSuffix}(\${$nameMin}));
        } catch (\\Throwable \$e) {
            return \$this->handleException(\$e);
        }
    }

    public function destroy({$name} \${$nameMin})
    {
        try {
            \$this->{$nameMin}Service->delete{$name}(\${$nameMin});
            return \$this->ok('{$name} deleted successfully');
        } catch (\\Throwable \$e) {
            return \$this->handleException(\$e);
        }
    }
}
PHP;

        File::put($controllerPath, $controllerContent);
    }
}

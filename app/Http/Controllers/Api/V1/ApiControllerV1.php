<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Interfaces\V1\ApiRenderableExceptionV1;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiControllerV1 extends Controller
{
    use ApiResponses;

    public function include(string $relationship): bool
    {
        $param = request()->get('include');

        if (!isset($param)) {
            return false;
        }

        $includesValues = explode(',', strtolower($param));

        return in_array(strtolower($relationship), $includesValues);
    }

    protected function handleException(Throwable $e)
    {
        if ($e instanceof ApiRenderableExceptionV1) {
            Log::error(get_class($e) . ': ' . $e->getUserMessage(), [
                'developer_hint' => $e->getDeveloperHint(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => $e->getStatusCode(),
            ], $e->getStatusCode());
        }

        Log::error("Unhandled Exception", ['exception' => $e]);

        return response()->json([
            'message' => 'An unexpected error occurred',
        ], 500);
    }
}
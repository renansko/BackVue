<?php

namespace App\Exceptions;

use App\Http\Responses\ApiModelErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    // Modified exception handler for API responses
    public function render($request, Throwable $exception): Response|JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'User is not authenticated',
                'status' => false,
                'error' => $exception->getMessage(),
            ], 401);
        }

        if ($exception instanceof ModelNotFoundException) {
            $model = $exception->getModel();
            $modelName = class_basename($model);
            $id = $request->route($modelName) ?? 'unknown';
            
            return response()->json([
                'message' => "{$modelName} with ID {$id} not found",
                'status' => false,
                'error' => $exception->getMessage(),
                'data' => [$modelName => null],
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Method not allowed for this route',
                'status' => false,
                'error' => $exception->getMessage(),
            ], 405);
        }

        if ($exception instanceof QueryException) {
            return response()->json([
                'message' => 'Invalid database query',
                'status' => false,
                'error' => $exception->getMessage()
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
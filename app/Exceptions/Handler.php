<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Não autenticado.'
                ], 401);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Os dados fornecidos são inválidos.',
                    'errors' => $e->errors()
                ], 422);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Recurso não encontrado.'
                ], 404);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'message' => $e->getMessage()
                ], $e->getStatusCode());
            }

            return response()->json([
                'message' => 'Ocorreu um erro no servidor.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }

        return parent::render($request, $e);
    }
}

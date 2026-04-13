<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $status = 500;
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof TokenMismatchException) {
                $status = 419;
            }

            $supportedStatuses = [403, 404, 419, 429, 500, 503];
            $view = in_array($status, $supportedStatuses, true) ? "errors.{$status}" : 'errors.generic';

            return response()->view($view, [
                'status' => $status,
            ], $status);
        });
    })->create();

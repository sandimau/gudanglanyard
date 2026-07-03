<?php

// Tingkatkan memory limit untuk mengatasi error memory exhausted
ini_set('memory_limit', '1024M');

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

if (file_exists($envFile = __DIR__.'/../.env')) {
    Dotenv\Dotenv::createImmutable(dirname($envFile))->safeLoad();
}

$basePath = parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?: '';
if ($basePath !== '' && $basePath !== '/') {
    $basePath = rtrim($basePath, '/');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $query = parse_url($requestUri, PHP_URL_QUERY);
    if (str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath)) ?: '/';
        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7) ?: '/';
        }
        $_SERVER['REQUEST_URI'] = $path . ($query ? '?' . $query : '');
    }
    $_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

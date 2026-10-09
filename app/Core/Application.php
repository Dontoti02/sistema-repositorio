<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Middleware\AsyncJobsMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\GuestOnly;
use App\Http\Middleware\PermissionRequired;
use App\Http\Middleware\RateLimit;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyCsrfToken;
use Closure;

/**
 * Núcleo de ejecución:
 * bootstrap → router → middleware → controlador → respuesta.
 */
final class Application
{
    private static ?self $instance = null;

    private Router $router;

    private bool $booted = false;

    private function __construct()
    {
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        Env::load(base_path('.env'));
        Config::boot(base_path('config'));

        date_default_timezone_set((string) config('app.timezone', 'UTC'));
        mb_internal_encoding('UTF-8');

        ExceptionHandler::register();
        Session::start();

        Container::getInstance();
    }

    /**
     * Ejecuta el ciclo completo de la petición.
     */
    public function run(?Request $request = null): Response
    {
        $this->boot();
        $request ??= Request::capture();

        try {
            return $this->dispatch($request);
        } catch (\Throwable $e) {
            return ExceptionHandler::toResponse($e);
        }
    }

    private function dispatch(Request $request): Response
    {
        $path = $request->path();

        // Instalación pendiente: solo el instalador.
        if (!$this->isInstalled()) {
            if (str_starts_with($path, '/instalar')) {
                $this->registerInstallRoutes();
            } else {
                return Response::redirect('/instalar');
            }
        } else {
            $this->registerRoutes();
        }

        // Resolución de institución (si la BD existe).
        if ($this->isInstalled()) {
            TenantContext::flush();
            TenantContext::institutionId();
        }

        $match = $this->router->match($request->method(), $path);
        if ($match === null) {
            throw new HttpException(404);
        }
        $route = $match['route'];
        $request->setRouteParams($match['params']);

        // Pipeline de middleware.
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (Closure $next, string $name): Closure {
                return function (Request $req) use ($name, $next): Response {
                    $middleware = $this->makeMiddleware($name);
                    return $middleware->handle($req, $next);
                };
            },
            function (Request $req) use ($route): Response {
                return $this->invoke($req, $route['handler']);
            }
        );

        // Middleware global de cabeceras de seguridad siempre aplica.
        $security = new SecurityHeaders();
        return $security->handle($request, $pipeline);
    }

    private function invoke(Request $request, mixed $handler): Response
    {
        if (is_callable($handler) && !is_array($handler)) {
            $result = $handler($request);
        } else {
            [$class, $method] = $handler;
            $controller = Container::getInstance()->get($class);
            $result = $controller->{$method}($request);
        }
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }
        return Response::html((string) $result);
    }

    private function makeMiddleware(string $name): Middleware
    {
        $param = null;
        if (str_contains($name, ':')) {
            [$name, $param] = explode(':', $name, 2);
        }
        return match ($name) {
            'auth' => new Authenticate(),
            'guest' => new GuestOnly(),
            'csrf' => new VerifyCsrfToken(),
            'permission' => new PermissionRequired($param ?? ''),
            'rate' => new RateLimit($param),
            'async' => new AsyncJobsMiddleware(),
            default => throw new \RuntimeException("Middleware no registrado: {$name}"),
        };
    }

    private function isInstalled(): bool
    {
        return is_file(storage_path('installed.lock'));
    }

    private function registerInstallRoutes(): void
    {
        $router = $this->router;
        require base_path('routes/install.php');
    }

    private function registerRoutes(): void
    {
        $router = $this->router;
        require base_path('routes/web.php');
        require base_path('routes/admin.php');
        require base_path('routes/api.php');
        require base_path('routes/oai.php');
    }
}

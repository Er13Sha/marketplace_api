<?php
declare(strict_types=1);

use App\Kernel;
use Runtime\Swoole\SymfonyHttpBridge;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpKernel\TerminableInterface;

/*
 * Dedicated Swoole entrypoint. Only THIS process uses the Swoole runtime;
 * bin/console and public/index.php keep the default Symfony runtime.
 */
$_SERVER['APP_RUNTIME'] = $_ENV['APP_RUNTIME'] = 'Runtime\\Swoole\\Runtime';

$workerNum = (int) ($_SERVER['SWOOLE_WORKER_NUM'] ?? $_ENV['SWOOLE_WORKER_NUM'] ?? 0);
if ($workerNum <= 0) {
    $workerNum = \function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 4;
}

// Consumed by Runtime\Swoole\ServerFactory.
$_SERVER['APP_RUNTIME_OPTIONS'] = $_ENV['APP_RUNTIME_OPTIONS'] = [
    'host' => $_SERVER['SWOOLE_HOST'] ?? $_ENV['SWOOLE_HOST'] ?? '0.0.0.0',
    'port' => (int) ($_SERVER['SWOOLE_PORT'] ?? $_ENV['SWOOLE_PORT'] ?? 8000),
    'settings' => [
        'worker_num' => $workerNum,
    ],
];

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context): callable {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();

    $container = $kernel->getContainer();
    $resetter = $container->has('services_resetter') ? $container->get('services_resetter') : null;

    // Returning a callable makes the Swoole runtime use its CallableRunner,
    // which lets us reset stateful services (Doctrine EM, profiler, ...) after
    // every request so nothing leaks across this long-running worker.
    return static function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($kernel, $resetter): void {
        $sfRequest = SymfonyHttpBridge::convertSwooleRequest($swooleRequest);
        $sfResponse = $kernel->handle($sfRequest);
        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $swooleResponse);

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($sfRequest, $sfResponse);
        }

        $resetter?->reset();
    };
};

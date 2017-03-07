<?php

namespace PhpCsStash;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use GuzzleHttp\Client;
use PhpCsStash\Api\ApiUser;
use PhpCsStash\Checker\CheckerFactory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

/**
 * Class StashApiServiceProvider
 *
 * @author Oleg Schelkunov <oleg.schelkunov@gmail.com>
 *
 */
class StashApiServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['monolog.phpcs'] = function () use ($app) {
            return new $app['monolog.logger.class']('phpcs');
        };

        $user = new ApiUser($app['stash']['username'], $app['stash']['password']);

        $client = new Client([
            'base_url' => sprintf("%s/rest/api/1.0/", rtrim($app['stash']['url'], '/')),
            'defaults' => [
                'timeout' => $app['stash']['timeout'],
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'allow_redirects' => true,
                'auth' => [$user->getUsername(), $user->getPassword()],
            ],
        ]);

        $stashApi = new StashApi($app['monolog.phpcs'], $client, $user);

        $type = $app['checker.type'];
        $checker = CheckerFactory::get($type, $app['checker.' . $type], $app['monolog.phpcs']);

        $app['phpcs.stash'] = function () use ($app, $stashApi, $checker) {
            return new Core($stashApi, $app['monolog.phpcs'], $checker);
        };
    }

    public function boot(Application $app)
    {
        $log = $app['monolog.phpcs'];
        $dir = $app['phpcs.logdir'];

        $log->pushHandler(
            new StreamHandler(
                __DIR__ . '/../' . $dir . '/info.' . date("Y-m-d").".log",
                $app['monolog.phpcs.info.level']
            )
        );

        $log->pushHandler(
            new StreamHandler(
                __DIR__ . '/../' . $dir . '/error.' . date("Y-m-d").".log",
                $app['monolog.phpcs.error.level']
            )
        );

        $log->pushHandler(new BrowserConsoleHandler());
    }
}

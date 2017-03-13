<?php
/**
 * @author Artem Naumenko
 * phpcs-stash app entry point. Accepts branch analysis requests.
 * Looks for updated pull-requests, analyses updated code,
 * and comments code that contains errors.
 */
require_once(__DIR__ . '/../vendor/autoload.php');

$env = getenv('APP_ENV') ?: 'prod';

$app = new Silex\Application();
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/$env.yml"));
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/../log/app.log',
]);
$app->register(new \PhpCsStash\StashApiServiceProvider(), [
    'phpcs.logdir' => __DIR__ . '/../log/',
]);

$app->get('/webhook/{branch}/{slug}/{repo}', function ($branch, $slug, $repo) use ($app) {
    $service = $app['phpcs.stash'];
    return $app->json($service->runSync(new \PhpCsStash\Api\BranchConfig($branch, $slug, $repo)));
})->assert('branch', '[\w-\/]+')->assert('slug', '[\w-]+')->assert('repo', '[\w-]+');

$app->run();

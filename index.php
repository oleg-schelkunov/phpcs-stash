<?php
/**
 * @author Artem Naumenko
 * Входящий скрипт для phpcs-stash. Принимает запросы на анализ изменений веток.
 * Находит пул реквесты в измененных ветках, анализирует измененный код, комментирует
 * строки с ошибками
 *
 */
require_once('vendor/autoload.php');

$core = new \PhpCsStash\Core(__DIR__."/configuration.ini");

$branch = isset($_GET['branch']) ? $_GET['branch'] : null;
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;
$repo = isset($_GET['repo']) ? $_GET['repo'] : null;

$config = new \PhpCsStash\Api\BranchConfig($branch, $slug, $repo);

try {
    var_dump($core->runSync($config));
} catch (\InvalidArgumentException $e) {
    header("HTTP/1.0 400 Bad Request");
}

<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Plugin.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use BEdita\Core\Filesystem\FilesystemRegistry;
use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Cache\Engine\NullEngine;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Chialab\Calendar\Test\TestApp\Application;
use Chialab\Calendar\Test\TestApp\Filesystem\Adapter\NullAdapter;
use Migrations\TestSuite\Migrator;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);
    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

define('ROOT', $root . DS . 'tests' . DS);
define('APP', ROOT . 'TestApp' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CORE_PATH', $root . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);

Configure::write('Error.ignoredDeprecationPaths', ['*/cakephp/src/TestSuite/Fixture/FixtureInjector.php']);
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'Chialab\Calendar\Test\TestApp',
    'encoding' => 'UTF-8',
    'paths' => [
        'plugins' => [ROOT . 'Plugin' . DS],
        'templates' => [APP . 'Template' . DS],
    ],
]);

Log::setConfig([
    'debug' => [
        'engine' => ConsoleLog::class,
        'levels' => ['notice', 'info', 'debug'],
    ],
    'error' => [
        'engine' => ConsoleLog::class,
        'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
    ],
]);

Cache::drop('_bedita_object_types_');
Cache::drop('_bedita_core_');
Cache::setConfig([
    '_cake_core_' => ['engine' => ArrayEngine::class],
    '_cake_model_' => ['engine' => ArrayEngine::class],
    '_bedita_object_types_' => ['className' => NullEngine::class],
    '_bedita_core_' => ['className' => NullEngine::class],
]);

ConnectionManager::setConfig('test', [
    'url' => getenv('db_dsn'),
    // 'log' => true,
]);
ConnectionManager::alias('test', 'default');

Router::reload();
Security::setSalt('BEDITA|BEDITA|BEDITA|BEDITA|BEDITA|BEDITA|BEDITA|BEDITA');

FilesystemRegistry::setConfig([
    'default' => ['className' => NullAdapter::class],
    'thumbnails' => ['className' => NullAdapter::class],
]);

$app = new Application(dirname(__DIR__) . '/config');
$app->bootstrap();
$app->pluginBootstrap();

// clear all before running tests
TableRegistry::getTableLocator()->clear();
Cache::clear('_cake_core_');
Cache::clear('_cake_model_');
Cache::clear('_bedita_object_types_');

// Run migrations
$migrator = new Migrator();
$migrator->run(['plugin' => 'BEdita/Core']);

<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\TestApp;

use Cake\Http\BaseApplication;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap()
    {
        $this->addPlugin('BEdita/Core');
    }

    /**
     * @inheritDoc
     */
    public function middleware($middlewareQueue)
    {
        return $middlewareQueue;
    }
}

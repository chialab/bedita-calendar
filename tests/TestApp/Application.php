<?php
declare(strict_types=1);

namespace Chialab\Calendar\Test\TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;

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
    public function bootstrap(): void
    {
        $this->addPlugin('BEdita/Core');
        $this->addPlugin('Chialab/Calendar');
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }
}

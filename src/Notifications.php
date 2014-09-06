<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

class Notifications
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $debug_address;

    /**
     * @var string
     */
    private $from_address;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;

        $this->debug = $this->config['notifications']['debug'];
        $this->debug_address = $this->config['notifications']['debug_address'];
        $this->from_address = $this->config['notifications']['from_address'];
    }

    /**
     *
     *
     * @since 1.0
     *
     */
    public function doX()
    {
    }

    /**
     *
     *
     * @since 1.0
     *
     */
    public function doY()
    {
    }
}

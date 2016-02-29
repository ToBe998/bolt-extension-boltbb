<?php

namespace Bolt\Extension\Bolt\BoltBB\Provider;

use Bolt\Extension\Bolt\BoltBB\Config\Config;
use Bolt\Extension\Bolt\BoltBB\Twig;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * BoltBB service provider
 *
 * Copyright (C) 2014  Gawain Lynch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @copyright Copyright (c) 2014, Gawain Lynch
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class BoltBBServiceProvider implements ServiceProviderInterface
{
    /** @var array */
    private $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function register(Application $app)
    {
        $app['boltbb.config'] = $app->share(
            function ($app) {
                return new Config($this->config);
            }
        );

        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(
                        new Twig\BoltBBExtension(
                            $app['boltbb.config']
                        )
                    );

                    return $twig;
                }
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function boot(Application $app)
    {
    }
}
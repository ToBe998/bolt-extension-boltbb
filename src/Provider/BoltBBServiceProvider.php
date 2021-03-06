<?php

namespace Bolt\Extension\Bolt\BoltBB\Provider;

use Bolt\Extension\Bolt\BoltBB\Admin\AdminAjaxRequest;
use Bolt\Extension\Bolt\BoltBB\Admin\Manager;
use Bolt\Extension\Bolt\BoltBB\Config\Config;
use Bolt\Extension\Bolt\BoltBB\Config\ContentTypes;
use Bolt\Extension\Bolt\BoltBB\Controller;
use Bolt\Extension\Bolt\BoltBB\Storage\Records;
use Bolt\Extension\Bolt\BoltBB\Twig;
use Bolt\Extension\Bolt\Members\Storage\Schema\Table\Forums;
use Pimple as Container;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * BoltBB service provider
 *
 * Copyright (C) 2014-2016 Gawain Lynch
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
            function () {
                return new Config($this->config);
            }
        );

        $app['boltbb.controller.frontend'] = $app->share(
            function ($app) {
                return new Controller\Frontend($app['boltbb.config']);
            }
        );

        $app['boltbb.controller.backend'] = $app->share(
            function ($app) {
                return new Controller\Backend($app['boltbb.config']);
            }
        );

        $app['boltbb.controller.ajax'] = $app->share(
            function ($app) {
                return new Controller\Ajax($app['boltbb.config']);
            }
        );

        $app['boltbb.repos'] = $app->share(
            function ($app) {
                return new Container([
                    'boltbb_forums'  => $app->share(function ($app) { return $app['storage']->getRepository(Forums::class); }),
                    'boltbb_topics'  => $app->share(function ($app) { return $app['storage']->getRepository('boltbb_topics'); }),
                    'boltbb_replies' => $app->share(function ($app) { return $app['storage']->getRepository('boltbb_replies'); }),
                ]);
            }
        );

        $app['boltbb.records'] = $app->share(
            function ($app) {
                return new Records($app['boltbb.config'], $app['boltbb.repos']);
            }
        );

        $app['boltbb.admin.manager'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );

        $app['boltbb.admin.request'] = $app->share(
            function ($app) {
                return new AdminAjaxRequest($app);
            }
        );


        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(new Twig\BoltBBExtension($app['boltbb.config'], $app['boltbb.records']));

                    return $twig;
                }
            )
        );

        $app['config'] = $app->share(
            $app->extend(
                'config',
                function ($config) use ($app) {
                    $boltContentTypes = $config->get('contenttypes');
                    $general = $config->get('general');
                    $method = new \ReflectionMethod('\Bolt\Config', 'parseContentType');
                    $method->setAccessible(true);

                    $forumsTypes = ContentTypes::getDefaultForums();
                    $topicsTypes = ContentTypes::getDefaultTopics();
                    $repliesTypes = ContentTypes::getDefaultReplies();

                    $boltContentTypes['bb_forums'] = $method->invoke($config, 'bb_forums', $forumsTypes, $general);
                    $boltContentTypes['bb_topics'] = $method->invoke($config, 'bb_topics', $topicsTypes, $general);
                    $boltContentTypes['bb_replies'] = $method->invoke($config, 'bb_replies', $repliesTypes, $general);

                    $config->set('contenttypes', $boltContentTypes);

                    return $config;
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

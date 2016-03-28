<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Bolt\Asset\File\JavaScript;
use Bolt\Controller\Zone;
use Bolt\Extension\Bolt\BoltBB\BoltBBExtension;
use Bolt\Extension\Bolt\BoltBB\Admin;
use Bolt\Extension\Bolt\BoltBB\Config\Config;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BoltBB admin area controller
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
class Backend implements ControllerProviderInterface
{
    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Silex\Application $app
     *
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        // Admin page
        $ctr->match('/', [$this, 'admin'])
            ->before([$this, 'before'])
            ->bind('BoltBBAdmin')
            ->method('GET')
        ;

        return $ctr;
    }

    /**
     * Controller before render
     *
     * @param Request     $request
     * @param Application $app
     */
    public function before(Request $request, Application $app)
    {
        /** @var BoltBBExtension $extension */
        $extension = $app['extensions']->get('Bolt/BoltBB');
        $dir = $extension->getWebDirectory()->getPath();

        // Add our JS & CSS
        $js = (new JavaScript('/' . $dir . '/js/boltbb.admin.js'))->setZone(Zone::BACKEND)->setPriority(20)->setLate(true);
        $app['asset.queue.file']->add($js);
    }

    /**
     * The main admin page
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Twig_Markup
     */
    public function admin(Application $app, Request $request)
    {
        $this->addTwigPath($app);

        $forums = $app['boltbb.admin.manager']->getForums();
        $needtypes = false;

        // Test to see if contenttypes have been set up
        foreach ($this->config['contenttypes'] as $type) {
            if (!$app['storage']->getContentType($type)) {
                $needtypes = true;
            }
        }

        // Set a flashbag if there is missing data
        if ($forums['needsync']) {
            $app['logger.flash']->error("Configured forums are missing from the database table.  Run 'Sync Table' to resolve.<br>");
        }
        if ($needtypes) {
            $app['logger.flash']->error("BoltBB ContentTypes are missing from contenttypes.yml.  Run 'Setup Contenttypes' to resolve.<br>");
        }

        $html = $app['twig']->render('@BoltBBAdmin/boltbb.twig', [
            'boltbb'    => $this->config['boltbb'],
            'base_uri'  => $this->config['base_uri'],
            'forums'    => $forums['forums'],
            'needsync'  => $forums['needsync'],
            'needtypes' => $needtypes,
            'hasrows'   => $forums['hasrows'],
        ]);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Add our Twig path
     *
     * @param \Silex\Application $app
     */
    private function addTwigPath(Application $app)
    {
        $boltBB = $app['extensions']->get('Bolt/BoltBB');
        $dir = $boltBB->getBaseDirectory()->getDir('templates/admin');
        $app['twig.loader.bolt_filesystem']->addDir($dir, '@BoltBBAdmin');
    }
}

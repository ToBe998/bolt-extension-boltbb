<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Silex;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Bolt\Translation\Translator as Trans;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Admin;
use Bolt\Extension\Bolt\BoltBB\Contenttypes;
use Bolt\Extension\Bolt\BoltBB\AdminAjaxRequest;

/**
 * BoltBB admin area controller
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
class BoltBBAdminController implements ControllerProviderInterface
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
     * @var Bolt\Extension\Bolt\BoltBB\Admin
     */
    private $admin;

    /**
     * @param \Silex\Application $app
     *
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->config = $app[Extension::CONTAINER]->config;
        $this->admin = new Admin($app);

        /**
         * @var $ctr \Silex\ControllerCollection
         */
        $ctr = $app['controllers_factory'];

        // Admin page
        $ctr->match('/', array($this, 'admin'))
            ->before(array($this, 'before'))
            ->bind('BoltBBAdmin')
            ->method('GET');

        // AJAX requests
        $ctr->match('/ajax', array($this, 'ajax'))
            ->bind('BoltBBAdminAjax')
            ->method('GET|POST');

        return $ctr;
    }

    /**
     * Controller before render
     *
     * @param Request            $request
     * @param \Silex\Application $app
     */
    public function before(Request $request, Application $app)
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $app['htmlsnippets'] = true;

        // Add our JS & CSS
        $app[Extension::CONTAINER]->addJavascript('js/boltbb.admin.js', true);
    }

    /**
     * The main admin page
     *
     * @param \Silex\Application $app
     * @param Request $request
     *
     * @return \Twig_Markup
     */
    public function admin(Application $app, Request $request)
    {
        $this->addTwigPath($app);

        $forums = $this->admin->getForums();
        $needtypes = false;

        // Test to see if contenttypes have been set up
        foreach ($this->config['contenttypes'] as $type) {
            if (!$app['storage']->getContentType($type)) {
                $needtypes = true;
            }
        }

        // Set a flashbag if there is missing data
        if ($forums['needsync']) {
            $app['session']->getFlashBag()->add('error', "Configured forums are missing from the database table.  Run 'Sync Table' to resolve.<br>" );
        }
        if ($needtypes) {
            $app['session']->getFlashBag()->add('error', "BoltBB contenttypes are missing from contenttypes.yml.  Run 'Setup Contenttypes' to resolve.<br>" );
        }

        $html = $app['render']->render('boltbb.twig', array(
            'boltbb'    => $this->config['boltbb'],
            'base_uri'  => $this->config['base_uri'],
            'forums'    => $forums['forums'],
            'needsync'  => $forums['needsync'],
            'needtypes' => $needtypes,
            'hasrows'   => $forums['hasrows']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * BoltBB Admin AJAX controller
     *
     * @param \Silex\Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function ajax(Application $app, Request $request)
    {
        // Get the task name
        $task = $app['request']->get('task');

        $allowedTasks = array('forumOpen', 'forumClose', 'forumSync', 'forumContenttypes', 'repairRelation', 'testNotify');

        if (!$task || !in_array($task, $allowedTasks)) {
            // Yeah, nah
            return new Response('Invalid request parameters', Response::HTTP_BAD_REQUEST);
        }

        $ar = new AdminAjaxRequest($app);

        if ($request->getMethod() === 'POST') {

            if ($task == 'forumOpen') {
                // Open a forum
                $forums = $request->request->get('forums');

                return $ar->forumOpen($forums);
            } elseif ($task == 'forumClose') {
                // Close a forum
                $forums = $request->request->get('forums');

                return $ar->forumClose($forums);
            } elseif ($task == 'repairRelation') {
                // Repair forum/reply relationships
                return $ar->repairRelation();
            } elseif ($task == 'testNotify') {
                // Send a test notification
                return $ar->testNotify();
            }

        } elseif ($request->getMethod() === 'GET') {

            if ($task == 'forumSync') {
                // Sync our database table with the configuration files defined forums
                return $ar->forumSync();
            } elseif ($task == 'forumContenttypes') {
                // Write our missing contenttypes into contentypes.yml
                return $ar->forumContenttypes();
            }

        }
    }

    /**
     * Add our Twig path
     *
     * @param \Silex\Application $app
     */
    private function addTwigPath(Application $app)
    {
        $app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/admin');
    }

}

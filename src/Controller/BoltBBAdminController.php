<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Silex;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Admin;
use Bolt\Extension\Bolt\BoltBB\Contenttypes;

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
     *
     * @param Silex\Application $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Silex\Application $app)
    {
        $this->config = $app[Extension::CONTAINER]->config;
        $this->admin = new Admin($app);

        // check if user has allowed role(s)
        $user    = $app['users']->getCurrentUser();
        $userid  = $user['id'];

        foreach ($this->config['admin_roles'] as $role) {
            if ($app['users']->hasRole($userid, $role)) {
                $this->authorized = true;
                break;
            }
        }

        if ($this->authorized) {
            /**
             * @var $ctr \Silex\ControllerCollection
             */
            $ctr = $app['controllers_factory'];

            // Admin page
            $ctr->match('/', array($this, 'admin'))
                ->before(array($this, 'before'))
                ->bind('admin')
                ->method('GET');

            // AJAX requests
            $ctr->match('/ajax', array($this, 'ajax'))
                ->before(array($this, 'before'))
                ->bind('ajax')
                ->method('GET|POST');

            $app[Extension::CONTAINER]->addMenuOption(__('BoltBB'), $app['paths']['bolt'] . 'extensions/boltbb', "fa fa-cog");

            return $ctr;
        }
    }

    /**
     * Controller before render
     *
     * @param Request           $request
     * @param \Bolt\Application $app
     */
    public function before(Request $request, \Bolt\Application $app)
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $app['htmlsnippets'] = true;

        // Add our JS & CSS
        $app[Extension::CONTAINER]->addJavascript('js/boltbb.admin.js', true);
    }

    /**
     * The main admin page
     *
     * @param Silex\Application $app
     * @param Request $request
     * @return \Twig_Markup
     */
    public function admin(Silex\Application $app, Request $request)
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
     * @param Silex\Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function ajax(Silex\Application $app, Request $request)
    {
        if ($request->getMethod() == "POST" && $app['request']->get('task')) {
            //
            //if (!$app['users']->checkAntiCSRFToken()) {
            //    $app->abort(400, __("Something went wrong"));
            //}

            //
            $values = array(
                'job' => $app['request']->get('task'),
                'result' => true
            );

            if ($app['request']->get('task') == 'forumOpen') {
                /*
                 * Open a forum
                 */
                if (! empty($request->request->get('forums'))) {
                    foreach ($request->request->get('forums') as $forum) {
                        try {
                            $this->admin->doForumOpen($forum);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'forumClose') {
                /*
                 * Close a forum
                 */
                if (! empty($request->request->get('forums'))) {
                    foreach ($request->request->get('forums') as $forum) {
                        try {
                            $this->admin->doForumClose($forum);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'repairRelation') {
                /*
                 * Repair forum/reply relationships
                 */
                try {
                    $this->admin->doRepairReplyRelationships();
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'testNotify') {
                /*
                 * Send a test notification
                 */
                try {
                    $this->admin->doTestNotification();
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }

                return new JsonResponse($values);
            }

            // Yeah, nah
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);

        } elseif ($request->getMethod() == "GET" && $app['request']->get('task')) {
            if ($app['request']->get('task') == 'forumSync') {

                /*
                 * Sync our database table with the configuration files defined forums
                 */
                try {
                    $this->admin->syncForumDbTables();

                    $values = $this->admin->getForums();

                    return new JsonResponse($values);
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }
            } elseif ($app['request']->get('task') == 'forumContenttypes') {

                /*
                 * Write our missing contenttypes into contentypes.yml
                 */
                $bbct = new Contenttypes($app);

                foreach ($this->config['contenttypes'] as $type => $values) {
                    if (! $bbct->isContenttype($type)) {
                        try {
                            $bbct->insertContenttype($type);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new Response('', Response::HTTP_OK, array('content-type' => 'text/html'));
            }

            // Yeah, nah
            return new Response('Invalid request parameters', Response::HTTP_BAD_REQUEST);
        }
    }

    private function addTwigPath(Silex\Application $app)
    {
        $app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/admin');
    }

}

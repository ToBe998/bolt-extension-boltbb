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
class Ajax implements ControllerProviderInterface
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
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        // AJAX requests
        $ctr->match('/ajax', [$this, 'ajax'])
            ->bind('BoltBBAdminAjax')
            ->method('GET|POST')
        ;

        return $ctr;
    }

    /**
     * BoltBB Admin AJAX controller route.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return Response|JsonResponse
     */
    public function ajax(Application $app, Request $request)
    {
        // Get the task name
        $task = $request->get('task');

        $allowedTasks = ['forumOpen', 'forumClose', 'forumSync', 'forumContenttypes', 'repairRelation', 'testNotify'];

        if (!$task || !in_array($task, $allowedTasks)) {
            // Yeah, nah
            return new Response('Invalid request parameters', Response::HTTP_BAD_REQUEST);
        }

        /** @var Admin\AdminAjaxRequest $adminRequest */
        $adminRequest = $app['boltbb.admin.request'];

        if ($request->isMethod('POST')) {
            if ($task === 'forumOpen') {
                // Open a forum
                $forums = $request->request->get('forums');

                return $adminRequest->forumOpen($forums);
            } elseif ($task === 'forumClose') {
                // Close a forum
                $forums = $request->request->get('forums');

                return $adminRequest->forumClose($forums);
            } elseif ($task === 'repairRelation') {
                // Repair forum/reply relationships
                return $adminRequest->repairRelation();
            } elseif ($task === 'testNotify') {
                // Send a test notification
                return $adminRequest->testNotify();
            }

            throw new \LogicException('Invalid task: ' . $task);
        }

        if ($task === 'forumSync') {
            // Sync our database table with the configuration files defined forums
            return $adminRequest->forumSync();
        } elseif ($task === 'forumContenttypes') {
            // Write our missing contenttypes into contentypes.yml
            return $adminRequest->forumContenttypes();
        }

        throw new \LogicException('Invalid task: ' . $task);
    }
}

<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * BoltBB administration request functions
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
class AdminAjaxRequest
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

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;
        $this->admin = new Admin($app);
    }

    /**
     * Open a forum(s)
     *
     * @param  array        $forums
     * @return JsonResponse
     */
    public function forumOpen(array $forums)
    {
        if ($forums && is_array($forums)) {
            foreach ($forums as $forum) {
                try {
                    $this->admin->doForumOpen($forum);
                } catch (\Exception $e) {
                    return new JsonResponse($this->getResult('forumOpen', $e), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        return new JsonResponse($this->getResult('forumOpen'));
    }

    /**
     * Close a forum(s)
     *
     * @param  array        $forums
     * @return JsonResponse
     */
    public function forumClose(array $forums)
    {
        if ($forums && is_array($forums)) {
            foreach ($forums as $forum) {
                try {
                    $this->admin->doForumClose($forum);
                } catch (\Exception $e) {
                    return new JsonResponse($this->getResult('forumClose', $e), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        return new JsonResponse($this->getResult('forumClose'));
    }

    /**
     * Sync our database table with the configuration files defined forums
     *
     * @return JsonResponse
     */
    public function forumSync()
    {
        try {
            $this->admin->syncForumDbTables();

            $this->admin->getForums();

            return new JsonResponse($this->getResult('forumSync'));
        } catch (\Exception $e) {
            return new JsonResponse($this->getResult('forumSync', $e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Write our missing contenttypes into contentypes.yml
     *
     * @return JsonResponse|Response
     */
    public function forumContenttypes()
    {
        $bbct = new Contenttypes($this->app);

        foreach ($this->config['contenttypes'] as $type => $values) {
            if (! $bbct->isContenttype($type)) {
                try {
                    $bbct->insertContenttype($type);
                } catch (\Exception $e) {
                    return new JsonResponse($this->getResult('forumContenttypes', $e), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        return new Response('', Response::HTTP_OK, array('content-type' => 'text/html'));
    }

    /**
     * Repair forum/reply relationships
     *
     * @return JsonResponse
     */
    public function repairRelation()
    {
        try {
            $this->admin->doRepairReplyRelationships();
        } catch (\Exception $e) {
            return new JsonResponse($this->getResult('repairRelation', $e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->getResult('repairRelation'));
    }

    /**
     * Send a test notification
     *
     * @return JsonResponse
     */
    public function testNotify()
    {
        try {
            $this->admin->doTestNotification();
        } catch (\Exception $e) {
            return new JsonResponse($this->getResult($task, $e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->getResult($task));
    }

    /**
     *
     * @param  string     $task
     * @param  \Exception $e
     * @return array
     */
    private function getResult($task, \Exception $e = null)
    {
        if (is_null($e)) {
            return array(
                'job'    => $task,
                'result' => true,
                'data'   => ''
            );
        }

        return array(
            'job'    => $task,
            'result' => true,
            'data'   => $e->getMessage()
        );
    }
}

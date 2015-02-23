<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

/**
 * Subscriptions management for BoltBB
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
class Subscriptions
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
     * @var Data
     */
    private $data;

    /**
     *
     * @param Silex\Application $app
     * @param mixed             $topic Topic ID or slug
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;
        $this->data = new Data($this->app);
    }

    /**
     * Get the subscribers for relevant to a topic
     *
     * Subscribers can be following a forum and/or a topic, however notifications
     * are only sent for new topics or replies.  Both are associated with a
     * fourm and therefore we will lookup who is subscribed to the forum (if not
     * a global topic) and include them
     *
     * @return array
     */
    public function getSubscribers($topic)
    {
        $topic = $this->data->getTopic($topic);
        $forum = $this->data->getForum($topic->values['forum']);
        $subscribers = array();

        // Get the topic subscribers
        if (! empty($topic->values['subscribers'])) {
            foreach (json_decode($topic->values['subscribers'], true) as $sub) {
                $subscribers[$sub] = false;
            }
        }

        // Get & combine the topic's forum subscribers
        if (! empty($forum['subscribers'])) {
            foreach (json_decode($forum['subscribers'], true) as $sub) {
                $subscribers[$sub] = false;
            }
        }

        // Add the profiles for subscribers
        if (! empty($subscribers)) {
            foreach ($subscribers as $id => $value) {
                $subscribers[$id] = $this->app['members']->getMember('id', $id);
            }
        }

        return $subscribers;
    }

    /**
     * Add a subscriber ID to the forum
     *
     * @param mixed $forum
     * @param int   $id    ID to add to subscription array
     */
    public function addSubscriberForum($forum, $id, $subscribers = array())
    {
        $forum = $this->data->getForum($forum);

        if ($forum === false) {
            return;
        }

        if (! empty($forum['subscribers'])) {
            $subscribers = json_decode($forum['subscribers'], true);
        }

        if (! in_array($id, $subscribers)) {
            $subscribers[] = (int) $id;
            $subscribers = json_encode($subscribers);

            $this->app['db']->update(
                $this->config['tables']['forums'],
                array('subscribers' => $subscribers),
                array('id' => $forum['id'])
            );
        }
    }

    /**
     * Add a subscriber ID to the topic
     *
     * Do direct to database to avoid PRE_SAVE & POST_SAVE hooks
     *
     * @param mixed $topic
     * @param int   $id    ID to add to subscription array
     */
    public function addSubscriberTopic($topic, $id, $subscribers = array())
    {
        $topic = $this->data->getTopic($topic);

        if ($topic === false) {
            return;
        }

        if (! empty($topic->values['subscribers'])) {
            $subscribers = json_decode($topic->values['subscribers'], true);
        }

        if (! in_array($id, $subscribers)) {
            $subscribers[] = (int) $id;
            $subscribers = json_encode($subscribers);

            $this->app['db']->update(
                $this->config['tables']['topics'],
                array('subscribers' => $subscribers),
                array('id' => $topic->values['id'])
            );
        }
    }

    /**
     * Remove a subscriber ID from the forum
     *
     * @param mixed $forum
     * @param int   $id    ID to add to subscription array
     */
    public function delSubscriberForum($forum,  $id, $subscribers = array())
    {
        $forum = $this->data->getForum($forum);

        if ($forum === false) {
            return;
        }

        if (! empty($forum['subscribers'])) {
            $subscribers = json_decode($forum['subscribers'], true);
        }

        // Remove the ID if present
        $subscribers = json_encode(array_diff($subscribers, array($id)));

        $this->app['db']->update(
            $this->config['tables']['forums'],
            array('subscribers' => $subscribers),
            array('id' => $forum['id'])
        );
    }

    /**
     * Remove a subscriber ID from the topic
     *
     * Do direct to database to avoid PRE_SAVE & POST_SAVE hooks
     *
     * @param mixed $topic
     * @param int   $id    ID to add to subscription array
     */
    public function delSubscriberTopic($topic,  $id, $subscribers = array())
    {
        $topic = $this->data->getTopic($topic);

        if ($topic === false) {
            return;
        }

        if (! empty($topic->values['subscribers'])) {
            $subscribers = json_decode($topic->values['subscribers'], true);
        }

        // Remove the ID if present
        $subscribers = json_encode(array_diff($subscribers, array($id)));

        $this->app['db']->update(
            $this->config['tables']['topics'],
            array('subscribers' => $subscribers),
            array('id' => $topic->values['id'])
        );
    }
}

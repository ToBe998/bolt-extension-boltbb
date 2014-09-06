<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Bolt\Extension\Bolt\ClientProfiles\ClientProfiles;

/**
 * Subscriptions management for BoltBB
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
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
     * @var ForumsData
     */
    private $data;

    /**
     *
     * @param Silex\Application $app
     * @param  mixed $topic Topic ID or slug
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->data = new ForumsData($this->app);
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
        $topic = $forums->getTopic($topic);
        $forum = $forums->getForum($topic->values['forum']);
        $subscribers = array();

        // Get the topic subscribers
        if (! empty($topic->values['subscribers'])){
            foreach (json_decode($this->topic->values['subscribers'], true) as $key => $value) {
                $subscribers[$value] = false;
            }
        }

        // Get & combine the topic's forum subscribers
        if (! empty($forum['subscribers'])) {
            foreach (json_decode($this->forum['subscribers'], true) as $key => $value) {
                $subscribers[$value] = false;
            }
        }

        // Add the profiles for subscribers
        if (! empty($subscribers)) {
            $profiles = new ClientProfiles($this->app);

            foreach ($subscribers as $id => $value) {
                $subscribers[$id] = $profiles->getClientProfile($id);
            }
        }

        return $subscribers;
    }

    /**
     * Add a subscriber ID to the forum
     *
     * @param mixed $forum
     * @param int   $id ID to add to subscription array
     */
    public function addSubscriberForum($forum, $id, $subscribers = array())
    {
        $forum = $this->data->getForum($forum);

        //array_diff( [312, 401, 1599, 3], [401] )
    }

    /**
     * Add a subscriber ID to the topic
     *
     * Do direct to database to avoid PRE_SAVE & POST_SAVE hooks
     *
     * @param mixed $topic
     * @param int   $id ID to add to subscription array
     */
    public function addSubscriberTopic($topic,  $id, $subscribers = array())
    {
        $topic = $this->data->getTopic($topic);
    }

    /**
     * Remove a subscriber ID from the forum
     *
     * @param mixed $forum
     * @param int   $id ID to add to subscription array
     */
    public function delSubscriberForum($forum,  $id, $subscribers = array())
    {
        $forum = $this->data->getForum($forum);
    }

    /**
     * Remove a subscriber ID from the topic
     *
     * Do direct to database to avoid PRE_SAVE & POST_SAVE hooks
     *
     * @param mixed $topic
     * @param int   $id ID to add to subscription array
     */
    public function delSubscriberTopic($topic,  $id, $subscribers = array())
    {
        $topic = $this->data->getTopic($topic);
    }
}

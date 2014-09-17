<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Bolt\Extension\Bolt\Members\MembersProfiles;

/**
 * BoltBB data management
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
class Data
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;
    }

    /**
     * Get an array that describes the forum regardless of requesting via
     * slug or forum ID
     *
     * @since 1.0
     *
     * @param mixed $input Either a slug or numeric ID for a forum
     *
     * @return array Details of the forum
     */
    public function getForum($forum)
    {
        if (is_numeric($forum)) {
            $query = "SELECT * FROM {$this->config['tables']['forums']} WHERE id = :id";
            $forum = $this->app['db']->fetchAssoc($query, array(':id' => $forum));
        } else {
            $query = "SELECT * FROM {$this->config['tables']['forums']} WHERE slug = :slug";
            $forum = $this->app['db']->fetchAssoc($query, array(':slug' => $forum));
        }

        if ($forum) {
            $forum['title'] = $this->config['forums'][$forum['slug']]['title'];
            $forum['description'] = $this->config['forums'][$forum['slug']]['description'];

            return $forum;
        }

        return false;
    }

    /**
     * G
     * @param  string|int $forum
     * @return array
     */
    public function getForumLastPost($forum = false)
    {
        if ($forum) {
            $forum = $this->getForum($forum);
            $params = array(
                'forum' => $forum['id'],
                'orderby' => '-datechanged',
                'returnsingle' => true
            );
        } else {
            $params = array(
                'orderby' => '-datechanged',
                'returnsingle' => true
            );
        }

        // Try for a reply first
        $record = $this->app['storage']->getContent($this->config['contenttypes']['replies'], $params);

        // Get the latest topic instead
        if (empty($record)) {
            $record = $this->app['storage']->getContent($this->config['contenttypes']['topics'], $params);

            if (empty($record)) {
                return false;
            }
        }

        // Fill in the author information if exists
        $profiles = new MembersProfiles($this->app);
        $record->values['authorprofile'] = $profiles->getMembersProfiles($record->values['author']);

        return $record;
    }

    /**
     * Get a slug for a given forum
     *
     * @param  mixed  $forum Either a slug or numeric ID for a forum
     * @return string Slug of requested forum
     */
    public function getForumSlug($forum)
    {
        $forum = $this->getForum($forum);

        return $forum['slug'];
    }

    /**
     * Get an array that describes the topic regardless of requesting via
     * slug or topic ID
     *
     * @param  mixed $topic_input Either a slug or numeric ID for a topic
     * @return array Details of the topic including replies
     */
    public function getTopic($topic_input)
    {
        //
        if (is_numeric($topic_input)) {
            $topic = $this->app['storage']->getContent($this->config['contenttypes']['topics'],
                array(
                    'id' => $topic_input,
                    'returnsingle' => true
            ));
        } else {
            $topic = $this->app['storage']->getContent($this->config['contenttypes']['topics'],
                array(
                    'slug' => $topic_input,
                    'returnsingle' => true
            ));
        }

        // Fill in the author information if exists
        $profiles = new MembersProfiles($this->app);
        $topic->values['authorprofile'] = $profiles->getMembersProfiles($topic->values['author']);

        return $topic;
    }

    /**
     * Lookup a forum's topics
     *
     * @since 1.0
     *
     * @param  int           $forum_id The ID of the forum to get topics for
     * @param  arary         $params   An optional associative array of WHERE parameters
     * @param  int           $limit    If set, page the output to the passed limit
     * @return \Bolt\Content
     */
    public function getForumTopics($forum_id, $params = false, $limit = false)
    {
        $query = array(
            'order' => '-datecreated',
            'returnsingle' => false
        );

        if ($forum_id) {
            $query['forum'] = $forum_id;
        }

        if ($limit) {
            $query['limit'] = $limit;
            $query['paging'] = true;
        }

        $records = $this->app['storage']->getContent($this->config['contenttypes']['topics'], $query, $pager, $params);

        if (! empty($records)) {
            $profiles = new MembersProfiles($this->app);
            foreach ($records as $record) {
                $record->values['authorprofile'] = $profiles->getMembersProfiles($record->values['author']);
            }
        }

        return $records;
    }

    /**
     * Lookup a forums topic count
     *
     * @since 1.0
     *
     * @param  integer $forum_id The ID of the forum to get topics for
     * @return array
     */
    public function getForumTopicCount($forum_id)
    {
        if (empty($forum_id)) {
            $query = "SELECT * FROM {$this->config['tables']['topics']}";
            $map = array();
        } else {
            $query = "SELECT * FROM {$this->config['tables']['topics']} WHERE forum = :forum";
            $map = array(':forum' => $forum_id);
        }

        return $this->app['db']->executeQuery($query, $map)->rowCount();
    }

    /**
     * Lookup a forums reply count
     *
     * @since 1.0
     *
     * @param  integer $forum_id The ID of the forum to get replies for
     * @return array
     */
    public function getForumReplyCount($forum_id)
    {
        if (empty($forum_id)) {
            $query = "SELECT * FROM {$this->config['tables']['replies']}";
            $map = array();
        } else {
            $query = "SELECT * FROM {$this->config['tables']['replies']} WHERE forum = :forum";
            $map = array(':forum' => $forum_id);
        }

        return $this->app['db']->executeQuery($query, $map)->rowCount();
    }

    /**
     * Get the unique resource identifier for a given forum
     *
     * @param  mixed  $forum
     * @param  bool   $relative
     * @return string
     */
    public function getForumURI($forum, $relative = true)
    {
        $forum = $this->getForum($forum);
        $uri = '/' . $this->config['base_uri'] . '/' . $forum['slug'];

        if ($relative) {
            return $uri;
        } else {
            // XXX
        }
    }

    /**
     * Lookup a topic's replies
     *
     * @since 1.0
     *
     * @param  int           $topic_id The ID of the topic to get replies for
     * @param  int           $limit    If set, page the output to the passed limit
     * @return \Bolt\Content
     */
    public function getTopicReplies($topic_id, $limit = false)
    {
        $query = array(
            'topic' => $topic_id,
            'order' => 'datecreated',
            'returnsingle' => false
        );

        if ($limit) {
            $query['limit'] = $limit;
            $query['paging'] = true;
        }

        $replies = $this->app['storage']->getContent($this->config['contenttypes']['replies'], $query);

        if (empty($replies)) {
            return false;
        }

        // Fill in the author information if exists
        $profiles = new MembersProfiles($this->app);

        foreach ($replies as $reply) {
            $reply->values['authorprofile'] = $profiles->getMembersProfiles($reply->values['author']);
        }

        return $replies;
    }

    /**
     * Lookup a topic's reply count
     *
     * @since 1.0
     *
     * @param  integer $topic_id The ID of the forum to get replies for
     * @return array
     */
    public function getTopicReplyCount($topic_id)
    {
        $query = "SELECT * FROM {$this->config['tables']['replies']} WHERE topic = :topic";
        $map = array(
            ':topic' => $topic_id
        );

        return $this->app['db']->executeQuery($query, $map)->rowCount();
    }

    /**
     * Lookup a topic's last post
     *
     * @since 1.0
     *
     * @param  integer $topic_id The ID of the topic to get last post for
     * @return array
     */
    public function getTopicLastPost($topic_id)
    {
        // Make sure if we're passed a slug that we have the actual ID
        if (! is_numeric($topic_id)) {
            $topic = $this->getTopic($topic_id);
            $topic_id = $topic['id'];
        }

        $record = $this->app['storage']->getContent(
            $this->config['contenttypes']['replies'],
            array(
                'orderby' => '-datechanged',
                'topic' => $topic_id,
                'returnsingle' => true
        ));

        // Fill in the author information if exists
        if (! empty($record)) {
            $profiles = new MembersProfiles($this->app);
            $record->values['authorprofile'] = $profiles->getMembersProfiles($record->values['author']);
        }

        return $record;
    }

    /**
     * Get the unique resource identifier for a given topic
     *
     * @param  mixed  $forum
     * @param  bool   $relative
     * @return string
     */
    public function getTopicURI($topic, $relative = true)
    {
        $topic = $this->getTopic($topic);
        $forum = $this->getForum($topic['forum']);

        $uri = '/' . $this->config['base_uri'] . '/' . $forum['slug'] . '/' . $topic['slug'];

        if ($relative) {
            return $uri;
        } else {
            // XXX
        }
    }

    /**
     * Return an array of recent entries, either topics or replies
     *
     * @since 1.0
     *
     * @param  string  $type  Either 'topic' or 'reply'
     * @param  integer $count Number of recent entries to return
     * @return array   Array of recent entries
     */
    public function getRecent($type, $count = 5)
    {
        return $this->app['storage']->getContent($type, array(
            'count' => $count,
            'returnsingle' => false
        ));
    }
}

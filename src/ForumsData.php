<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Symfony\Component\HttpFoundation\Request;

class ForumsData
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
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
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
            return $this->app['storage']->getContent('topics', array(
                'id' => $topic_input,
                'returnsingle' => true
            ));
        } else {
            return $this->app['storage']->getContent('topics', array(
                'slug' => $topic_input,
                'returnsingle' => true
            ));
        }
    }

    /**
     * Lookup a forum's topics
     *
     * @since 1.0
     *
     * @param  integer $forum_id The ID of the forum to get topics for
     * @param  array   $pager
     * @return array
     */
    public function getForumTopics($forum_id, &$pager = array())
    {
        return $this->app['storage']->getContent('topics', array('forum' => $forum_id), $pager);
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
     * Lookup a forums last post
     *
     * @since 1.0
     *
     * @param  integer $forum_id The ID of the forum to get last post for
     * @return array
     */
    public function getForumLastPost($forum_id)
    {
        $forum = $this->getForum($forum_id);

        return $this->app['storage']->getContent('topics', array(
            'forum' => $forum['id'],
            'returnsingle' => true
        ));
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
     * @param  integer $forum_id The ID of the forum to get topics replies for
     * @param  integer $topic_id The ID of the topic to get replies for
     * @return array
     */
    public function getTopicReplies($topic_id, &$pager = array())
    {
        return $this->app['storage']->getContent('replies', array(
            'topic' => $topic_id,
            'order' => 'datecreated',
            'returnsingle' => false),
            $pager);
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
     * @param  integer $forum_id The ID of the forum to get last post for
     * @param  integer $topic_id The ID of the topic to get last post for
     * @return array
     */
    public function getTopicLastPost($topic_id)
    {
        $topic = $this->getTopic($topic_id);

        return $this->app['storage']->getContent('topics', array(
            'topic' => $topic['id'],
            'returnsingle' => true
        ));
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

    /**
     * Render the breadcrumbs
     *
     * @return \Twig_Markup
     */
    public function getBreadcrumbs($forum_id = null)
    {
        if (empty($forum_id)) {
            $forum = '';
        } else {
            $forum = $this->getForum($forum_id);
        }

        $html = $this->app['render']->render($this->config['templates']['breadcrumbs'], array(
            'forum' => $forum,
            'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }
}

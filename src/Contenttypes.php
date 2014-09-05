<?php

/**
 * Default Contenttypes definitions
 *
 */

namespace Bolt\Extension\Bolt\BoltBB;

/**
 * Content override class
 */
class Contenttypes
{
    /**
     * Contenttype defaults for Topics
     *
     * @var array
     */
    private $topics;

    /**
     * Contenttype defaults for Replies
     *
     * @var array
     */
    private $replies;

    public function __construct()
    {
        $this->setTopics();
        $this->setReplies();
    }

    /**
     * Getter for topics contenttype array
     *
     * @return array
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * Getter for replies contenttype array
     *
     * @return array
     */
    public function getReplies()
    {
        return $this->replies;
    }

    /**
     * Setter for topics array
     *
     * @return void
     */
    private function setTopics()
    {
        $this->topics = array(
            'name' => 'Topics',
            'singular_name' => 'Topic',
            'fields' => array(
                'title' => array(
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic'
                ),
                'body' => array(
                    'type'    => 'html',
                    'height'  => '300px'
                ),
                'author' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'info'    => '',
                    'readonly' => 'true',
                    'group'   => 'Info'
                ),
                'authorip' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'label'   => 'IP address',
                    'readonly' => 'true'
                ),
                'forum' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => 'true'
                ),
                'state' => array(
                    'type'    => 'select',
                    'values'  => array(
                        'open',
                        'closed'
                    ),
                    'variant' => 'inline'
                ),
                'visibility' => array(
                    'type'    => 'select',
                    'values'  => array(
                        'nomal',
                        'pinned',
                        'global'
                    ),
                    'variant' => 'inline'
                ),
                'subscribers' => array(
                    'type' => 'textarea',
                    'readonly' => 'true',
                    'hidden' => 'true'
                ),
            ),
            'default_status' => 'published',
        );
    }

    /**
     * Setter for replies array
     *
     * @return void
     */
    private function setReplies()
    {
        $this->replies = array(
            'name' => 'Replies',
            'singular_name' => 'Reply',
            'fields' => array(
                'title' => array(
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic'
                ),
                'body' => array(
                    'type'    => 'html',
                    'height'  => '300px'
                ),
                'author' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'info'    => '',
                    'readonly' => 'true',
                    'group'   => 'Info'
                ),
                'authorip' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'label'   => 'IP address',
                    'readonly' => 'true'
                ),
                'forum' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => 'true'
                ),
                'topic' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => 'true'
                )
            ),
            'default_status' => 'published',
        );
    }
}
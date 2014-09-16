<?php

namespace Bolt\Extension\Bolt\BoltBB\Twig;

use Bolt\Extension\Bolt\BoltBB\Data;
use Bolt\Extension\Bolt\BoltBB\Extension;

/**
 * Twig functions
 */
class BoltBBExtension extends \Twig_Extension
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
     * @var Bolt\Extension\Bolt\BoltBB\Data
     */
    private $data;

    /**
     * @var \Twig_Environment
     */
    private $twig = null;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->data = new Data($app);
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * Return the name of the extension
     */
    public function getName()
    {
        return 'boltbb.extension';
    }

    /**
     * The functions we add
     */
    public function getFunctions()
    {
        return array(
            'forumsbreadcrumbs' => new \Twig_Function_Method($this, 'forumsBreadcrumbs'),
            'forumslug'         => new \Twig_Function_Method($this, 'forumSlug'),
            'forumtopiccount'   => new \Twig_Function_Method($this, 'forumTopicCount'),
            'forumreplycount'   => new \Twig_Function_Method($this, 'forumReplyCount'),
            'topicreplycount'   => new \Twig_Function_Method($this, 'topicReplyCount'),
            'lastpost'          => new \Twig_Function_Method($this, 'lastPost'),
        );
    }

    /**
     * Return the HTML for a breadcrumb menu
     *
     * @param  integer      $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumsBreadcrumbs($forum_id = false)
    {
        if (empty($forum_id)) {
            $forum = '';
        } else {
            $forum = $this->data->getForum($forum_id);
        }

        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/navigation');

        $html = $this->app['render']->render($this->config['templates']['navigation']['crumbs'], array(
            'forum' => $forum,
            'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param  integer      $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumSlug($forum_id)
    {
        $html = $this->data->getForumSlug($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param  integer      $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumTopicCount($forum_id)
    {
        $html = $this->data->getForumTopicCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums reply count to the template
     *
     * @since 1.0
     *
     * @param  integer      $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumReplyCount($forum_id)
    {
        $html = $this->data->getForumReplyCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a topic's reply count to the template
     *
     * @since 1.0
     *
     * @param  integer      $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function topicReplyCount($topic_id)
    {
        $html = $this->data->getTopicReplyCount($topic_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return the last post record for the passed forum
     *
     * @param  integer       $forum_id The ID of the forum
     * @return \Bolt\Content
     */
    public function lastPost($record = false)
    {
        if (gettype($record) == 'object') {
            $lastpost = $this->data->getTopicLastPost($record->values['id']);

            if ($lastpost) {
                return $lastpost;
            }

            // We are the last post, return ourselves
            return $record;
        }

        return $this->data->getForumLastPost($record['id']);
    }
}

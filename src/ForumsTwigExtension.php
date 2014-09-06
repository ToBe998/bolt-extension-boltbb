<?php

namespace Bolt\Extension\Bolt\BoltBB;

/**
 * Twig functions
 */
class ForumsTwigExtension extends \Twig_Extension
{
    private $twig = null;

    public function __construct(\Silex\Application $app)
    {
        $this->forums = new Forums($app);
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
        return 'forums';
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
        );
    }

    /**
     * Return the HTML for a breadcrumb menu
     *
     * @param integer $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumsBreadcrumbs($forum_id = false)
    {
        $html = $this->forums->getBreadcrumbs($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumSlug($forum_id)
    {
        $html = $this->forums->getForumSlug($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumTopicCount($forum_id)
    {
        $html = $this->forums->getForumTopicCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums reply count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function forumReplyCount($forum_id)
    {
        $html = $this->forums->getForumReplyCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a topic's reply count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     * @return \Twig_Markup
     */
    public function topicReplyCount($topic_id)
    {
        $html = $this->forums->getTopicReplyCount($topic_id);

        return new \Twig_Markup($html, 'UTF-8');
    }
}

<?php

namespace Bolt\Extension\Bolt\BoltBB\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Data;
use Bolt\Extension\Bolt\BoltBB\Discussions;

class Frontend
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
     * @var Bolt\Extension\Bolt\BoltBB\Discussions
     */
    private $discuss;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->data = new Data($this->app);
        $this->discuss = new Discussions($this->app);
    }

    /**
     * Controller before render
     */
    public function before()
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $this->app['htmlsnippets'] = true;

        // Add our JS & CSS and CKeditor
        $this->app['extensions.' . Extension::NAME]->addCSS($this->config['stylesheet'], false);
        $this->app['extensions']->addJavascript($this->app['paths']['app'] . 'view/lib/ckeditor/ckeditor.js', true);
        $this->app['extensions.' . Extension::NAME]->addJavascript($this->config['javascript'], true);

        $this->app['extensions.' . Extension::NAME]->addCSS('/css/jquery.cssemoticons.css', false);
        $this->app['extensions.' . Extension::NAME]->addJavascript('/js/jquery.cssemoticons.min.js', true);
    }

    /**
     * Default route callback for forums
     *
     * @since 1.0
     */
    public function index($forums = array())
    {
        // Add assets to Twig path
        $this->addTwigPath();

        // Combine YAML and database information about each forum
        foreach ($this->config['forums'] as $key => $forum) {
            $forums[$key] = $this->data->getForum($key);
        }

        $html = $this->app['render']->render(
            $this->config['templates']['index'], array(
                'twigparent' => $this->config['parent_template'],
                'contenttypes' => $this->config['contenttypes'],
                'pagercount' => $this->config['pagercount'],
                'forums' => $forums,
                'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Default route callback for uncategorised forum feed
     *
     * @since 1.0
     */
    public function uncategorised($forums = array())
    {
        // Add assets to Twig path
        $this->addTwigPath();

        $html = $this->app['render']->render(
            $this->config['templates']['uncategorised'], array(
                'twigparent' => $this->config['parent_template'],
                'contenttypes' => $this->config['contenttypes'],
                'pagercount' => $this->config['pagercount'],
                'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Individual forum route callback
     *
     * @since 1.0
     *
     * @param object $request The Symonfy request object
     * @param mixed  $forum   Either ID or slug of the forum
     */
    public function forum(Request $request, $forum)
    {
        // Add assets to Twig path
        $this->addTwigPath();
        $forum = $this->data->getForum($forum);

        // Create and handle submission form
        $view = $this->discuss->doTopicForm($request, $forum);
        if (get_class($view) == 'Symfony\Component\HttpFoundation\RedirectResponse') {
            return $view;
        }

        $html = $this->app['render']->render($this->config['templates']['forum'], array(
            'form' => $view,
            'twigparent' => $this->config['parent_template'],
            'contenttypes' => $this->config['contenttypes'],
            'forum' => $forum,
            'global' => $this->data->getForumTopics(false,
                  array('visibility' => 'global')
                  ),
            'pinned' => $this->data->getForumTopics($forum['id'],
                  array('visibility' => 'pinned')
                  ),
            'topics' => $this->data->getForumTopics($forum['id'],
                  array('visibility' => 'normal',
                        'state' => 'open || closed'
                  ),
                  $this->config['pagercount']),
            'showpager' => $this->app['storage']->isEmptyPager() ? false : true,
            'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Individual topic route callback
     *
     * @since 1.0
     *
     * @param object $request The Symonfy request object
     * @param mixed  $forum   Either ID or slug of the forum
     * @param mixed  $topic   Either ID or slug of the topic
     */
    public function topic(Request $request, $forum, $topic)
    {
        // Add assets to Twig path
        $this->addTwigPath();

        // Get consistent info for forum and topic
        $forum = $this->data->getForum($forum);
        $topic = $this->data->getTopic($topic);

        // Create and handle submission form
        $view = $this->discuss->doReplyForm($request, $forum, $topic);
        if (get_class($view) == 'Symfony\Component\HttpFoundation\RedirectResponse') {
            return $view;
        }

        $html = $this->app['render']->render($this->config['templates']['topic'], array(
            'form' => $view,
            'twigparent' => $this->config['parent_template'],
            'contenttypes' => $this->config['contenttypes'],
            'forum' => $forum,
            'topic' => $topic,
            'replies' => $this->data->getTopicReplies($topic->values['id'], $this->config['pagercount']),
            'showpager' => $this->app['storage']->isEmptyPager() ? false : true,
            'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets');
    }

}

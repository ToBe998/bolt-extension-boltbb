<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Data;
use Bolt\Extension\Bolt\BoltBB\Discussions;
use Bolt\Extension\Bolt\BoltBB\Entity\Topic;
use Bolt\Extension\Bolt\BoltBB\Entity\Reply;
use Bolt\Extension\Bolt\BoltBB\Form\TopicType;
use Bolt\Extension\Bolt\BoltBB\Form\ReplyType;
use Bolt\Extension\Bolt\Members\Members;

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

        // Add jQuery CSS Emoticons Plugin @see: http://os.alfajango.com/css-emoticons/
        $this->app['extensions.' . Extension::NAME]->addCSS('css/jquery.cssemoticons.css', false);
        $this->app['extensions.' . Extension::NAME]->addJavascript('js/jquery.cssemoticons.min.js', true);

        // If using CKEditor CodeSnippet, enable Highlight.js
        if ($this->config['editor']['addons']['codesnippet']) {
            $this->app['extensions.' . Extension::NAME]->addCSS('js/ckeditor/plugins/codesnippet/lib/highlight/styles/default.css', false);
            $this->app['extensions.' . Extension::NAME]->addJavascript('js/ckeditor/plugins/codesnippet/lib/highlight/highlight.pack.js', true);
        }
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

        // Add the uncategorised version
        $forums['all'] = array(
            'id'          => 0,
            'slug'        => 'all',
            'state'       => 'closed',
            'subscribers' => '',
            'title'       => __('All Discussions'),
            'description' => __('The uncategorised version')
        );

        // Combine YAML and database information about each forum
        foreach ($this->config['forums'] as $key => $forum) {
            $forums[$key] = $this->data->getForum($key);
        }

        // Render the Twig
        $html = $this->app['render']->render(
            $this->config['templates']['forums']['index'], array(
                'twigparent' => $this->config['templates']['parent'],
                'contenttypes' => $this->config['contenttypes'],
                'forums' => $forums,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Default route callback for uncategorised forum feed
     *
     * @since 1.0
     */
    public function all($forums = array())
    {
        // Add assets to Twig path
        $this->addTwigPath();

        // Render the Twig
        $html = $this->app['render']->render(
            $this->config['templates']['forums']['forum'], array(
                'form' => '',
                'twigparent' => $this->config['templates']['parent'],
                'contenttypes' => $this->config['contenttypes'],
                'forum' => array(
                    'id'          => 0,
                    'slug'        => 'all',
                    'state'       => 'closed',
                    'subscribers' => '',
                    'title'       => __('All Discussions'),
                    'description' => __('The uncategorised version')
                ),
                'global' => $this->data->getForumTopics(false,
                    array('visibility' => 'global')
                ),
                'pinned' => $this->data->getForumTopics(false,
                    array('visibility' => 'pinned')
                ),
                'topics' => $this->data->getForumTopics(false,
                    array('visibility' => 'normal',
                        'state' => 'open || closed'
                    ),
                    $this->config['pagercount']),
                'showpager' => $this->app['storage']->isEmptyPager() ? false : true,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
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

        // Create new reply submission form
        $members = new Members($this->app);
        $topic = new Topic();
        $data = array('data' => array('forum_id' => $forum['id'], 'author' => $members->isAuth()));
        $form = $this->app['form.factory']->createBuilder(new TopicType(), $topic, $data)
                                          ->getForm();

        // Handle the form request data
        $form->handleRequest($request);

        // If we're in a POST, validate the form
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                // Create the new topic
                $topicid = $this->discuss->doTopicNew($request, $forum);

                // Get the new topic's URI
                $uri = $this->data->getTopicURI($topicid);

                // Redirect to the new topic
                return $this->app->redirect($uri);
            }
        }

        // Add CKEditor config javascript
        $js = $this->app['render']->render(
            '_editorconfig.twig', array(
                'ckconfig' => $this->config['editor'],
                'boltbb_basepath' => $this->app['extensions.' . Extension::NAME]->getBaseUrl()
        ));
        $this->app['extensions.' . Extension::NAME]->addSnippet(SnippetLocation::BEFORE_JS, $js);

        // Render the Twig
        $html = $this->app['render']->render(
            $this->config['templates']['forums']['forum'], array(
                'form' => $form,
                'twigparent' => $this->config['templates']['parent'],
                'contenttypes' => $this->config['contenttypes'],
                'form' => $form->createView(),
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
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
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

        // Create new reply submission form
        $members = new Members($this->app);
        $reply = new Reply();
        $data = array('data' => array('topic_id' => $topic['id'], 'author' => $members->isAuth()));
        $form = $this->app['form.factory']->createBuilder(new ReplyType(), $reply, $data)
                                          ->getForm();

        // Handle the form request data
        $form->handleRequest($request);

        // If we're in a POST, validate the form
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                // Create new reply
                $replyid = $this->discuss->doReplyNew($request, $topic);

                // Redirect
                return $this->app->redirect($request->getRequestUri() . '#reply-' . $topic['id'] . '-' . $replyid);
            }
        }

        // Add CKEditor config javascript
        $js = $this->app['render']->render(
            '_editorconfig.twig', array(
                'ckconfig' => $this->config['editor'],
                'boltbb_basepath' => $this->app['extensions.' . Extension::NAME]->getBaseUrl()
        ));
        $this->app['extensions.' . Extension::NAME]->addSnippet(SnippetLocation::BEFORE_JS, $js);

        // If using CKEditor CodeSnippet, enable Highlight.js
        if ($this->config['editor']['addons']['codesnippet']) {
            $js = '<script>hljs.initHighlightingOnLoad();</script>';
            $this->app['extensions.' . Extension::NAME]->addSnippet(SnippetLocation::END_OF_BODY, $js);
        }

        // Render the Twig
        $html = $this->app['render']->render(
            $this->config['templates']['forums']['topic'], array(
                'form' => $form->createView(),
                'twigparent' => $this->config['templates']['parent'],
                'contenttypes' => $this->config['contenttypes'],
                'forum' => $forum,
                'topic' => $topic,
                'replies' => $this->data->getTopicReplies($topic->values['id'], $this->config['pagercount']),
                'showpager' => $this->app['storage']->isEmptyPager() ? false : true,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets');
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/forums');
    }

}

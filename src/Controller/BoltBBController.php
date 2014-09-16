<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Silex;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Data;
use Bolt\Extension\Bolt\BoltBB\Discussions;
use Bolt\Extension\Bolt\BoltBB\Entity\Topic;
use Bolt\Extension\Bolt\BoltBB\Entity\Reply;
use Bolt\Extension\Bolt\BoltBB\Form\TopicType;
use Bolt\Extension\Bolt\BoltBB\Form\ReplyType;
use Bolt\Extension\Bolt\Members\Members;

class BoltBBController implements ControllerProviderInterface
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

    /**
     *
     * @param  Silex\Application           $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Silex\Application $app)
    {
        $this->config = $app[Extension::CONTAINER]->config;
        $this->data = new Data($app);
        $this->discuss = new Discussions($app);

        /**
         * @var $ctr \Silex\ControllerCollection
         */
        $ctr = $app['controllers_factory'];

        /*
         * Routes for forum base, individual forums and individual topics
         */
        $ctr->get('/', array($this, 'index'))
            ->before(array($this, 'before'))
            ->bind('index');

        $ctr->get('/all', array($this, 'all'))
            ->before(array($this, 'before'))
            ->bind('all');

        $ctr->match('/{forum}', array($this, 'forum'))
            ->before(array($this, 'before'))
            ->assert('forum', '[a-zA-Z0-9_\-]+')
            ->bind('forum')
            ->method('GET|POST');

        $ctr->match('/{forum}/{topic}', array($this, 'topic'))
            ->before(array($this, 'before'))
            ->assert('forum', '[a-zA-Z0-9_\-]+')
            ->assert('topic', '[a-zA-Z0-9_\-]+')
            ->bind('topic')
            ->method('GET|POST');

        return $ctr;
    }

    /**
     * Controller before render
     *
     * @param Request           $request
     * @param \Bolt\Application $app
     */
    public function before(Request $request, \Bolt\Application $app)
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $app['htmlsnippets'] = true;

        // Add our JS & CSS and CKeditor
        $app[Extension::CONTAINER]->addCSS('css/' . $this->config['webassets']['stylesheet'], false);
        $app['extensions']->addJavascript($app['paths']['app'] . 'view/lib/ckeditor/ckeditor.js', true);
        $app[Extension::CONTAINER]->addJavascript('js/' . $this->config['webassets']['javascript'], true);

        // Add jQuery CSS Emoticons Plugin @see: http://os.alfajango.com/css-emoticons/
        $app[Extension::CONTAINER]->addCSS('css/jquery.cssemoticons.css', false);
        $app[Extension::CONTAINER]->addJavascript('js/jquery.cssemoticons.min.js', true);

        // If using CKEditor CodeSnippet, enable Highlight.js
        if ($this->config['editor']['addons']['codesnippet']) {
            $app[Extension::CONTAINER]->addCSS('js/ckeditor/plugins/codesnippet/lib/highlight/styles/default.css', false);
            $app[Extension::CONTAINER]->addJavascript('js/ckeditor/plugins/codesnippet/lib/highlight/highlight.pack.js', true);
        }
    }

    /**
     * Default route callback for forums
     *
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return \Twig_Markup
     */
    public function index(Silex\Application $app, Request $request)
    {
        // Add assets to Twig path
        $this->addTwigPath($app);

        $forums = array();

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
        $html = $app['render']->render(
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
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return \Twig_Markup
     */
    public function all(Silex\Application $app, Request $request)
    {
        // Add assets to Twig path
        $this->addTwigPath($app);

        // Render the Twig
        $html = $app['render']->render(
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
                'showpager' => $app['storage']->isEmptyPager() ? false : true,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Individual forum route callback
     *
     * @param  Silex\Application $app
     * @param  Request           $request
     * @param  mixed             $forum   Either ID or slug of the forum
     * @return \Twig_Markup
     */
    public function forum(Silex\Application $app, Request $request, $forum)
    {
        // Add assets to Twig path
        $this->addTwigPath($app);
        $forum = $this->data->getForum($forum);

        // Create new reply submission form
        $topic = new Topic();
        $data = array('csrf_protection' => $this->config['csrf']);
        $form = $app['form.factory']->createBuilder(new TopicType(), $topic, $data)
                                    ->getForm();

        // Handle the form request data
        $form->handleRequest($request);

        // If we're in a POST, validate the form
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                // Check that we've got a valid member
                $author = $this->getMemberID($app);

                if ($author) {
                    // Create the new topic
                    $topicid = $this->discuss->doTopicNew($request, $forum, $author);

                    // Get the new topic's URI
                    $uri = $this->data->getTopicURI($topicid);

                    // Redirect to the new topic
                    return $app->redirect($uri);
                }
            }
        }

        // Add CKEditor config javascript
        $js = $app['render']->render(
            '_editorconfig.twig', array(
                'ckconfig' => $this->config['editor'],
                'ckfield'  => 'topic[body]',
                'boltbb_basepath' => $app[Extension::CONTAINER]->getBaseUrl()
        ));
        $app[Extension::CONTAINER]->addSnippet(SnippetLocation::BEFORE_JS, $js);

        // Render the Twig
        $html = $app['render']->render(
            $this->config['templates']['forums']['forum'], array(
                'form' => $form->createView(),
                'twigparent' => $this->config['templates']['parent'],
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
                'showpager' => $app['storage']->isEmptyPager() ? false : true,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Individual topic route callback
     *
     * @param  Silex\Application $app
     * @param  Request           $request
     * @param  mixed             $forum   Either ID or slug of the forum
     * @param  mixed             $topic   Either ID or slug of the topic
     * @return \Twig_Markup
     */
    public function topic(Silex\Application $app, Request $request, $forum, $topic)
    {
        // Add assets to Twig path
        $this->addTwigPath($app);

        // Get consistent info for forum and topic
        $forum = $this->data->getForum($forum);
        $topic = $this->data->getTopic($topic);

        // Create new reply submission form
        $reply = new Reply();
        $data = array('csrf_protection' => $this->config['csrf']);
        $form = $app['form.factory']->createBuilder(new ReplyType(), $reply, $data)
                                    ->getForm();

        // Handle the form request data
        $form->handleRequest($request);

        // If we're in a POST, validate the form
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                // Check that we've got a valid member
                $author = $this->getMemberID($app);

                if ($author) {
                    // Create new reply
                    $replyid = $this->discuss->doReplyNew($request, $topic, $author);

                    // Redirect
                    return $app->redirect($request->getRequestUri() . '#reply-' . $topic['id'] . '-' . $replyid);
                }
            }
        }

        // Add CKEditor config javascript
        $js = $app['render']->render(
            '_editorconfig.twig', array(
                'ckconfig' => $this->config['editor'],
                'ckfield'  => 'reply[body]',
                'boltbb_basepath' => $app[Extension::CONTAINER]->getBaseUrl()
        ));
        $app[Extension::CONTAINER]->addSnippet(SnippetLocation::BEFORE_JS, $js);

        // If using CKEditor CodeSnippet, enable Highlight.js
        if ($this->config['editor']['addons']['codesnippet']) {
            $js = '<script>hljs.initHighlightingOnLoad();</script>';
            $app[Extension::CONTAINER]->addSnippet(SnippetLocation::END_OF_BODY, $js);
        }

        // Render the Twig
        $html = $app['render']->render(
            $this->config['templates']['forums']['topic'], array(
                'form' => $form->createView(),
                'twigparent' => $this->config['templates']['parent'],
                'contenttypes' => $this->config['contenttypes'],
                'forum' => $forum,
                'topic' => $topic,
                'replies' => $this->data->getTopicReplies($topic->values['id'], $this->config['pagercount']),
                'showpager' => $app['storage']->isEmptyPager() ? false : true,
                'boltbb' => $this->config['boltbb'],
                'base_uri'  => $this->config['base_uri'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     *
     * @param Silex\Application $app
     */
    private function addTwigPath(Silex\Application $app)
    {
        $app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets');
        $app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/forums');
    }

    /**
     *
     * @param Silex\Application $app
     */
    private function getMemberID(Silex\Application $app)
    {
        $members = new Members($app);

        return $members->isAuth();
    }

}

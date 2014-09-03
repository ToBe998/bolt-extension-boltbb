<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;

class Controller
{
    private $app;
    private $functions;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->functions = new Functions($this->app);
    }

    /**
     * Controller before render
     */
    public function before()
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $this->app['htmlsnippets'] = true;

        // Add our JS & CSS and CKeditor
        $this->app['extensions.' . Extension::NAME]->addCSS($this->config['stylesheet'] , false);
        $this->app['extensions']->addJavascript($this->app['paths']['app'] . 'view/lib/ckeditor/ckeditor.js', true);
        $this->app['extensions.' . Extension::NAME]->addJavascript($this->config['javascript'], true);
    }

    /**
     * Default route callback for forums
     *
     * @since 1.0
     */
    public function Index($forums = array())
    {
        // Add assets to Twig path
        $this->addTwigPath();

        $func = new Functions($this->app, $this->config);

        // Combine YAML and database information about each forum
        foreach ($this->config['forums'] as $key => $forum) {
            $forums[$key] = $func->getForum($key);
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
    public function Uncategorised($forums = array())
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
     * @param mixed $forum Either ID or slug of the forum
     */
    public function Forum(Request $request, $forum)
    {
        // Add assets to Twig path
        $this->addTwigPath();

        $forum = $this->functions->getForum($forum);
        $constraints = array('constraints' => new Assert\NotBlank());

        $data = array();
        $form = $this->app['form.factory']
                        ->createBuilder('form', $data,  array('csrf_protection' => $this->config['csrf']))
                            ->add('title',  'text',     array('constraints' => new Assert\NotBlank()))
                            ->add('editor', 'textarea', array('constraints' => new Assert\NotBlank(),
                                                              'label' => false,
                                                              'attr' => array('style' => 'height: 150px;')))
                            ->add('author', 'hidden',   array('data' => '-1'))
                            ->add('post',   'submit',   array('label' => 'Post new topic'))
                            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Create the new topic
            $topicid = $this->functions->doNewTopic($request, $forum);

            // Get the new topic's URI
            $uri = $this->functions->getTopicURI($forum['id'], $topicid);

            // Redirect to the new topic
            return $this->app->redirect($uri);
        }

        $view = $form->createView();

        $html = $this->app['render']->render($this->config['templates']['forum'], array(
            'form' => $view,
            'twigparent' => $this->config['parent_template'],
            'contenttypes' => $this->config['contenttypes'],
            'pagercount' => $this->config['pagercount'],
            'forum' => $forum,
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
     * @param mixed $forum Either ID or slug of the forum
     * @param mixed $topic Either ID or slug of the topic
     */
    public function Topic(Request $request, $forum, $topic)
    {
        // Add assets to Twig path
        $this->addTwigPath();

        // Get consistent info for forum and topic
        $forum = $this->functions->getForum($forum);
        $topic = $this->functions->getTopic($forum['id'], $topic);

        $data = array();
        $form = $this->app['form.factory']
                        ->createBuilder('form', $data,  array('csrf_protection' => $this->config['csrf']))
                            ->add('editor', 'textarea', array('constraints' => new Assert\NotBlank(),
                                                              'label' => false,
                                                              'attr' => array('style' => 'height: 150px;')))
                            ->add('author', 'hidden',   array('data' => '-1'))
                            ->add('notify', 'checkbox', array('label' => 'Notify me of updates to this topic',
                                                              'data' => true))
                            ->add('post',   'submit',   array('label' => 'Post reply'))
                            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Create new reply
            $replyid = $this->functions->doNewReply($request, $forum, $topic);

            //
            return $this->app->redirect($request->getRequestUri() . '#reply-' . $forum['id'] . '-' . $topic['id'] . '-' . $replyid);
        }

        $view = $form->createView();

        $html = $this->app['render']->render($this->config['templates']['topic'], array(
            'form' => $view,
            'twigparent' => $this->config['parent_template'],
            'contenttypes' => $this->config['contenttypes'],
            'pagercount' => $this->config['pagercount'],
            'forum' => $forum,
            'topic' => $topic,
            'topic_author' => $topic['author'],
            'boltbb' => $this->config
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/assets');
    }

}
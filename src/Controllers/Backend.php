<?php

namespace Bolt\Extension\Bolt\BoltBB\Controllers;

use Silex;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Functions;

class Backend
{
    private $app;
    private $functions;

    public function __construct(Application $app)
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
//         $this->app['extensions.' . Extension::NAME]->addCSS($this->config['stylesheet'] , false);
        $this->app['extensions.' . Extension::NAME]->addJavascript('/js/boltbb.admin.js', true);
    }

    /**
     * The main admin page
     *
     * @return \Twig_Markup
     */
    public function admin(Application $app, Request $request)
    {
        $this->addTwigPath();

        $forums = array();
        $missing = false;

        foreach ($this->config['forums'] as $key => $values) {
            //
            $record = $this->functions->getForum($key);

            $forums[$key] = array(
                'name' => $values['title'],
                'description' => $values['description'],
                'state' => empty($record) ? 'missing' : $record['state'],
                'topics' => empty($record) ? '-' : $this->functions->getForumTopicCount($record['id']),
                'replies' => empty($record) ? '-' : $this->functions->getForumReplyCount($record['id'])
            );

            // If any of the forums are missing from the database, set a flag
            if (empty($record)) {
                $missing = true;
            }
        }

        $html = $this->app['render']->render('boltbb_admin.twig', array(
            'forums' => $forums,
            'boltbb' => $this->config['boltbb'],
            'missing' => $missing
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    public function ajax(Application $app, Request $request)
    {
        if ($request->getMethod() == "POST") {
            //
            //if (!$this->app['users']->checkAntiCSRFToken()) {
            //    $this->app->abort(400, __("Something went wrong"));
            //}

            //
            $values = array(
                'job' => $app['request']->get('task'),
                'result' => true
            );

            //
            if ($app['request']->get('task')) {
                if ($app['request']->get('task') == 'forumOpen') {

                    //
                    return new JsonResponse($values);
                } elseif ($app['request']->get('task') == 'forumClose') {

                    //
                    return new JsonResponse($values);
                }
            }
        } elseif ($request->getMethod() == "GET") {
            if ($app['request']->get('task')) {
                if ($app['request']->get('task') == 'forumRepair') {

                    $this->functions->syncForumDbTables();

                    return new JsonResponse('ok');
                }
            }
        }
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets');
    }

}
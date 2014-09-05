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
use Bolt\Extension\Bolt\BoltBB\Contenttypes;

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
        $needsync = false;
        $needtypes = false;

        // Get forum data and check if the table is in sync with the config
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
                $needsync = true;
            }
        }

        // Test to see if contenttypes have been set up
        foreach ($this->config['contenttypes'] as $type) {
            if (!$this->app['storage']->getContentType($type)) {
                $needtypes = true;
            }
        }

        // Set a flashbag if there is missing data
        if ($needsync) {
            $this->app['session']->getFlashBag()->add('error', "BoltBB configured forums are missing from the database table.  Run 'Sync Table' to resolve." );
        }
        if ($needtypes) {
            $this->app['session']->getFlashBag()->add('error', "BoltBB contenttypes haven't been added to contenttypes.yml.  Run 'Setup Contenttypes' to resolve." );
        }

        $html = $this->app['render']->render('boltbb_admin.twig', array(
            'forums' => $forums,
            'boltbb' => $this->config['boltbb'],
            'needsync' => $needsync,
            'needtypes' => $needtypes
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    public function ajax(Application $app, Request $request)
    {
        if ($request->getMethod() == "POST" && $app['request']->get('task')) {
            //
            //if (!$this->app['users']->checkAntiCSRFToken()) {
            //    $this->app->abort(400, __("Something went wrong"));
            //}

            //
            $values = array(
                'job' => $app['request']->get('task'),
                'result' => true
            );

            if ($app['request']->get('task') == 'forumOpen') {
                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'forumClose') {
                return new JsonResponse($values);
            }

            // Yeah, nah
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);

        } elseif ($request->getMethod() == "GET" && $app['request']->get('task')) {
            if ($app['request']->get('task') == 'forumSync') {

                // Sync our database table with the configuration files defined forums
                try {
                    $this->functions->syncForumDbTables();

                    $values = $this->functions->getForums();

                    return new JsonResponse($values);
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }
            } elseif ($app['request']->get('task') == 'forumContenttypes') {

                // Write our missing contenttypes into contentypes.yml
                $bbct = new Contenttypes($this->app);

                foreach ($this->config['contenttypes'] as $type => $values) {
                    if (! $bbct->isContenttype($type)) {
                        try {
                            $bbct->insertContenttype($type);

                            return new Response('', Response::HTTP_OK, array('content-type' => 'text/html'));
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }

                    }
                }
            }

            // Yeah, nah
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets');
    }

}
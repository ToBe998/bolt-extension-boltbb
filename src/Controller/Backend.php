<?php

namespace Bolt\Extension\Bolt\BoltBB\Controller;

use Silex;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Bolt\Extension\Bolt\BoltBB\Extension;
use Bolt\Extension\Bolt\BoltBB\Admin;
use Bolt\Extension\Bolt\BoltBB\Contenttypes;

class Backend
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
     * @var Bolt\Extension\Bolt\BoltBB\Admin
     */
    private $admin;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->admin = new Admin($this->app);
    }

    /**
     * Controller before render
     */
    public function before()
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $this->app['htmlsnippets'] = true;

        // Add our JS & CSS
//         $this->app['extensions.' . Extension::NAME]->addCSS($this->config['stylesheet'] , false);
        $this->app['extensions.' . Extension::NAME]->addJavascript('js/boltbb.admin.js', true);
    }

    /**
     * The main admin page
     *
     * @return \Twig_Markup
     */
    public function admin(Application $app, Request $request)
    {
        $this->addTwigPath();

        $forums = $this->admin->getForums();
        $needtypes = false;

        // Test to see if contenttypes have been set up
        foreach ($this->config['contenttypes'] as $type) {
            if (!$this->app['storage']->getContentType($type)) {
                $needtypes = true;
            }
        }

        // Set a flashbag if there is missing data
        if ($forums['needsync']) {
            $this->app['session']->getFlashBag()->add('error', "Configured forums are missing from the database table.  Run 'Sync Table' to resolve.<br>" );
        }
        if ($needtypes) {
            $this->app['session']->getFlashBag()->add('error', "BoltBB contenttypes are missing from contenttypes.yml.  Run 'Setup Contenttypes' to resolve.<br>" );
        }

        $html = $this->app['render']->render('boltbb.twig', array(
            'boltbb'    => $this->config['boltbb'],
            'base_uri'  => $this->config['base_uri'],
            'forums'    => $forums['forums'],
            'needsync'  => $forums['needsync'],
            'needtypes' => $needtypes,
            'hasrows'   => $forums['hasrows']
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
                /*
                 * Open a forum
                 */
                if (! empty($request->request->get('forums'))) {
                    foreach ($request->request->get('forums') as $forum) {
                        try {
                            $this->admin->doForumOpen($forum);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'forumClose') {
                /*
                 * Close a forum
                 */
                if (! empty($request->request->get('forums'))) {
                    foreach ($request->request->get('forums') as $forum) {
                        try {
                            $this->admin->doForumClose($forum);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'repairRelation') {
                /*
                 * Repair forum/reply relationships
                 */
                try {
                    $this->admin->doRepairReplyRelationships();
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }

                return new JsonResponse($values);
            } elseif ($app['request']->get('task') == 'testNotify') {
                /*
                 * Send a test notification
                 */
                try {
                    $this->admin->doTestNotification();
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }

                return new JsonResponse($values);
            }

            // Yeah, nah
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);

        } elseif ($request->getMethod() == "GET" && $app['request']->get('task')) {
            if ($app['request']->get('task') == 'forumSync') {

                /*
                 * Sync our database table with the configuration files defined forums
                 */
                try {
                    $this->admin->syncForumDbTables();

                    $values = $this->admin->getForums();

                    return new JsonResponse($values);
                } catch (Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                }
            } elseif ($app['request']->get('task') == 'forumContenttypes') {

                /*
                 * Write our missing contenttypes into contentypes.yml
                 */
                $bbct = new Contenttypes($this->app);

                foreach ($this->config['contenttypes'] as $type => $values) {
                    if (! $bbct->isContenttype($type)) {
                        try {
                            $bbct->insertContenttype($type);
                        } catch (Exception $e) {
                            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, array('content-type' => 'text/html'));
                        }
                    }
                }

                return new Response('', Response::HTTP_OK, array('content-type' => 'text/html'));
            }

            // Yeah, nah
            return new Response('Invalid request parameters', Response::HTTP_BAD_REQUEST);
        }
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)) . '/assets/admin');
    }

}

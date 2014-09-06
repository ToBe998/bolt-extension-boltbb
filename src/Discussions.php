<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Symfony\Component\HttpFoundation\Request;

class Discussions
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
    }

    /**
     * Create a new topic
     *
     * @since 1.0
     *
     */
    public function doNewTopic(Request $request, $forum)
    {
        // Get form
        $form = $request->get('form');

        $values = array(
            'slug' => makeSlug($form['title'], 128),
            'title' => $form['title'],
            'author' => $form['author'],
            'authorip' => $request->getClientIp(),
            'forum' => $forum['id'],
            'state' => 'open',
            'body' => $form['editor']
        );

        $record = $this->app['storage']->getEmptyContent($this->config['contenttypes']['topics']);
        $record->setValues($values);

        $id = $this->app['storage']->saveContent($record);

        if ($id === false) {
            $this->app['session']->getFlashBag()->set('error', 'There was an error posting the topic.');

            return null;
        } else {
            $this->app['session']->getFlashBag()->set('success', 'Topic posted.');

            return $id;
        }
    }

    /**
     * Create a new reply
     *
     * @since 1.0
     *
     */
    public function doNewReply(Request $request, $topic)
    {
        // Get form
        $form = $request->get('form');

        $values = array(
            'slug' => makeSlug($topic['title'], 128),
            'title' => $topic['title'],
            'author' => $form['author'],
            'authorip' => $request->getClientIp(),
            'topic' => $topic['id'],
            'body' => $form['editor']
        );

        $record = $this->app['storage']->getEmptyContent($this->config['contenttypes']['replies']);
        $record->setValues($values);

        $id = $this->app['storage']->saveContent($record);

        if ($id === false) {
            $this->app['session']->getFlashBag()->set('error', 'There was an error posting the reply.');

            return null;
        } else {
            $this->app['session']->getFlashBag()->set('success', 'Reply posted.');

            return $id;
        }
    }
}

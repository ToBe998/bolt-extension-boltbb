<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Maid\Maid;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 */
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

    /**
     * @var Bolt\Extension\Bolt\BoltBB\Data
     */
    private $data;

    /**
     * @var array Options to pass to Maid
     */
    private $maidOptions;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->data = new Data($this->app);

        $this->maidOptions = array(
            'allowed-tags' => array(
                'section', 'footer',
                'div', 'p', 'strong', 'em',
                'i', 'b', 'u', 's', 'sup', 'sub',
                'li', 'ul', 'ol', 'menu',
                'blockquote', 'pre', 'code', 'tt',
                'hr', 'br',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'dd', 'dl', 'dh',
                'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr',
                'img', 'header', 'cite', 'a', 'iframe'
            ),
            'allowed-attribs' => array(
                'id', 'class', 'style', 'name', 'value',
                'href', 'target', 'rel', 'src',
                'data-footnote-id',
                'data-resizetype', 'data-align', 'data-oembed',
                'allowfullscreen', 'allowscriptaccess',
                'scrolling', 'frameborder',
                'width', 'height'
            )
        );
    }

    /**
     * Create a new topic
     *
     * @since 1.0
     *
     */
    public function doTopicNew(Request $request, $forum)
    {
        // Hire a maid
        $maid = new Maid($this->maidOptions);

        // Get form
        $form = $request->get('form');

        $values = array(
            'slug'        => makeSlug($form['title'], 128),
            'title'       => $form['title'],
            'author'      => $form['author'],
            'authorip'    => $request->getClientIp(),
            'forum'       => $form['forum'],
            'state'       => 'open',
            'visibility'  => 'normal',
            'body'        => $maid->clean($form['editor']),
            'subscribers' => json_encode(array($form['author']))
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
    public function doReplyNew(Request $request, $topic)
    {
        // Hire a maid
        $maid = new Maid($this->maidOptions);

        // Get form
        $form = $request->get('form');

        $values = array(
            'slug'     => makeSlug($topic['title'], 128),
            'title'    => '[' . __('Reply') . ']:' . $topic['title'],
            'author'   => $form['author'],
            'authorip' => $request->getClientIp(),
            'forum'    => $topic['forum'],
            'topic'    => $form['topic'],
            'body'     => $maid->clean($form['editor'])
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

    public function doTopicForm(Request $request, $forum)
    {
        //
        $data = array();
        $form = $this->app['form.factory']
                        ->createBuilder('form', $data,  array('csrf_protection' => $this->config['csrf']))
                            ->add('title',  'text',     array('constraints' => new Assert\NotBlank()))
                            ->add('editor', 'textarea', array('constraints' => new Assert\NotBlank(),
                                                              'label' => false,
                                                              'attr'  => array('style' => 'height: 150px;')))
                            ->add('forum',  'hidden',   array('data'  => $forum['id']))
                            ->add('author', 'hidden',   array('data'  => '-1'))
                            ->add('post',   'submit',   array('label' => 'Post new topic'))
                            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Create the new topic
            $topicid = $this->doTopicNew($request, $forum);

            // Get the new topic's URI
            $uri = $this->data->getTopicURI($topicid);

            // Redirect to the new topic
            return $this->app->redirect($uri);
        }

        return $form->createView();
    }

    public function doReplyForm(Request $request, $forum, $topic)
    {
        $data = array();
        $form = $this->app['form.factory']
                        ->createBuilder('form', $data,  array('csrf_protection' => $this->config['csrf']))
                            ->add('editor', 'textarea', array('constraints' => new Assert\NotBlank(),
                                                              'label' => false,
                                                              'attr'  => array('style' => 'height: 150px;')))
                            ->add('topic',  'hidden',   array('data'  => $topic['id']))
                            ->add('author', 'hidden',   array('data'  => '-1'))
                            ->add('notify', 'checkbox', array('label' => 'Notify me of updates to this topic',
                                                              'data'  => true))
                            ->add('post',   'submit',   array('label' => 'Post reply'))
                            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Create new reply
            $replyid = $this->doReplyNew($request, $topic);

            // Redirect
            return $this->app->redirect($request->getRequestUri() . '#reply-' . $forum['id'] . '-' . $topic['id'] . '-' . $replyid);
        }

        return $form->createView();
    }
}

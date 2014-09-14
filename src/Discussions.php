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
            'subscribers' => json_encode(array((int) $form['author']))
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
            'title'    => '[' . __('Reply') . ']: ' . $topic['title'],
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
            // Check if the author wanted to subscribe and do as asked
            if (isset($form['notify'])) {
                $subs = new Subscriptions($this->app);
                $subs->addSubscriberTopic($form['topic'], $form['author']);
            }

            $this->app['session']->getFlashBag()->set('success', 'Reply posted.');

            return $id;
        }
    }
}

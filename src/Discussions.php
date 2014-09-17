<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Maid\Maid;
use Silex;
use Symfony\Component\HttpFoundation\Request;

/**
 * BoltBB discussion class
 *
 * Copyright (C) 2014  Gawain Lynch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @copyright Copyright (c) 2014, Gawain Lynch
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
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
        $this->config = $this->app[Extension::CONTAINER]->config;
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
    public function doTopicNew(Request $request, $forum, $author)
    {
        // Hire a maid
        $maid = new Maid($this->maidOptions);

        // Get form
        $form = $request->get('topic');

        $values = array(
            'slug'        => makeSlug($form['title'], 128),
            'title'       => $form['title'],
            'author'      => $author,
            'authorip'    => $request->getClientIp(),
            'forum'       => $forum['id'],
            'state'       => 'open',
            'visibility'  => 'normal',
            'body'        => $maid->clean($form['body']),
            'subscribers' => json_encode(array((int) $author))
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
    public function doReplyNew(Request $request, $topic, $author)
    {
        // Hire a maid
        $maid = new Maid($this->maidOptions);

        // Get form
        $form = $request->get('reply');

        $values = array(
            'slug'     => makeSlug($topic['title'], 128),
            'title'    => '[' . __('Reply') . ']: ' . $topic['title'],
            'author'   => $author,
            'authorip' => $request->getClientIp(),
            'forum'    => $topic['forum'],
            'topic'    => $topic['id'],
            'body'     => $maid->clean($form['body'])
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
                $subs->addSubscriberTopic($topic['id'], $author);
            }

            $this->app['session']->getFlashBag()->set('success', 'Reply posted.');

            return $id;
        }
    }
}

<?php

namespace Bolt\Extension\Bolt\BoltBB\Twig;

use Bolt\Extension\Bolt\BoltBB\Config\Config;
use Bolt\Extension\Bolt\BoltBB\Data;

/**
 * Twig functions for BoltBB
 *
 * Copyright (C) 2014-2016 Gawain Lynch
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
class BoltBBExtension extends \Twig_Extension
{
    /** @var Config */
    private $config;
    /** @var Data */
    private $data;

    /**
     * @var \Twig_Environment
     */
    private $twig = null;

    public function __construct(Config $config, Data $data)
    {
        $this->config = $config;
        $this->data = $data;
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * Return the name of the extension
     */
    public function getName()
    {
        return 'BoltBB';
    }

    /**
     * The functions we add
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html'], 'is_safe_callback' => true];
        $env  = ['needs_environment' => true];

        return [
            new \Twig_SimpleFunction('forumsbreadcrumbs', [$this, 'forumsBreadcrumbs'], $env),
            new \Twig_SimpleFunction('forumslug',         [$this, 'forumSlug']),
            new \Twig_SimpleFunction('forumtopiccount',   [$this, 'forumTopicCount']),
            new \Twig_SimpleFunction('forumreplycount',   [$this, 'forumReplyCount']),
            new \Twig_SimpleFunction('topicreplycount',   [$this, 'topicReplyCount']),
            new \Twig_SimpleFunction('lastpost',          [$this, 'lastPost']),
        ];
    }

    /**
     * Return the HTML for a breadcrumb menu
     *
     * @param \Twig_Environment $twig
     * @param bool|int          $forum_id The ID of the forum
     *
     * @return \Twig_Markup
     */
    public function forumsBreadcrumbs(\Twig_Environment $twig, $forum_id = false)
    {
        if ($forum_id === false) {
            $forum = '';
        } else {
            $forum = $this->data->getForum($forum_id);
        }

        $template = $this->config->getTemplate('navigation', 'crumbs');
        $html = $twig->render($template, [
            'forum'  => $forum,
            'boltbb' => $this->config,
        ]);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     *
     * @return \Twig_Markup
     */
    public function forumSlug($forum_id)
    {
        $html = $this->data->getForumSlug($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums topic count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     *
     * @return \Twig_Markup
     */
    public function forumTopicCount($forum_id)
    {
        $html = $this->data->getForumTopicCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a forums reply count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     *
     * @return \Twig_Markup
     */
    public function forumReplyCount($forum_id)
    {
        $html = $this->data->getForumReplyCount($forum_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return a topic's reply count to the template
     *
     * @since 1.0
     *
     * @param integer $forum_id The ID of the forum
     *
     * @return \Twig_Markup
     */
    public function topicReplyCount($topic_id)
    {
        $html = $this->data->getTopicReplyCount($topic_id);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Return the last post record for the passed forum
     *
     * @param integer $forum_id The ID of the forum
     *
     * @return \Bolt\Storage\Entity\Entity
     */
    public function lastPost($record = false)
    {
        if (gettype($record) === 'object') {
            $lastpost = $this->data->getTopicLastPost($record->values['id']);

            if ($lastpost) {
                return $lastpost;
            }

            // We are the last post, return ourselves
            return $record;
        }

        return $this->data->getForumLastPost($record['id']);
    }
}

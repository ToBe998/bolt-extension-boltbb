<?php

namespace Bolt\Extension\Bolt\BoltBB\Config;

use Silex\Application;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Default Contenttypes definitions
 *
 * NOTE:
 * Yes this is over-engineered!  It is a placeholder until Bolt catches up with
 * extension defined contenttypes
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
class ContentTypes
{
    /**
     * Getter for forums array.
     *
     * @return array
     */
    public static function getDefaultForums()
    {
        return [
            'name'          => 'BoltBB Forums',
            'singular_name' => 'BoltBB Forum',
            'fields'        => [
                'title' => [
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic',
                ],
                'slug' => [
                    'type'    => 'slug',
                    'uses'    => 'title',
                ],
                'state' => [
                    'type'    => 'select',
                    'info'    => 'Open: Topics can be made<br><br>Closed: Replies are closed',
                    'values'  => [
                        'open',
                        'closed',
                    ],
                    'variant' => 'inline',
                ],
                'subscribers' => [
                    'type'     => 'textarea',
                    'readonly' => true,
                    'hidden'   => true,
                ],
            ],
            'default_status' => 'published',
            'viewless'       => true,
        ];
    }

    /**
     * Getter for topics array.
     *
     * @return array
     */
    public static function getDefaultTopics()
    {
        return [
            'name'          => 'BoltBB Topics',
            'singular_name' => 'BoltBB Topic',
            'fields'        => [
                'title' => [
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic',
                ],
                'slug' => [
                    'type'    => 'slug',
                    'uses'    => 'title',
                ],
                'body' => [
                    'type'    => 'html',
                    'height'  => '300px',
                ],
                'author' => [
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'info'     => 'The GUID of the author',
                    'readonly' => true,
                    'group'    => 'Info',
                ],
                'authorip' => [
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'label'    => 'IP address',
                    'readonly' => true,
                ],
                'forum' => [
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true,
                ],
                'state' => [
                    'type'    => 'select',
                    'info'    => 'Open: Replies can be made<br><br>Closed: Replies are closed',
                    'values'  => [
                        'open',
                        'closed',
                    ],
                    'variant' => 'inline',
                ],
                'visibility' => [
                    'type'    => 'select',
                    'variant' => 'inline',
                    'info'    => 'Global: List at top of all forums<br><br>Pinned: List at the top of the specified form (below any global)<br><br>Normal: Listed newest first in the specified forum',
                    'values'  => [
                        'normal',
                        'pinned',
                        'global',
                    ],
                ],
                'subscribers' => [
                    'type'     => 'textarea',
                    'readonly' => true,
                    'hidden'   => true,
                ],
            ],
            'default_status' => 'published',
            'viewless'       => true,
        ];
    }

    /**
     * Getter for replies array.
     *
     * @return array
     */
    public static function getDefaultReplies()
    {
        return [
            'name'          => 'BoltBB Replies',
            'singular_name' => 'BoltBB Reply',
            'fields'        => [
                'title' => [
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic',
                ],
                'slug' => [
                    'type'    => 'slug',
                    'uses'    => 'title',
                ],
                'body' => [
                    'type'    => 'html',
                    'height'  => '300px',
                ],
                'author' => [
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'info'     => 'The GUID of the author',
                    'readonly' => true,
                    'group'    => 'Info',
                ],
                'authorip' => [
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'label'    => 'IP address',
                    'readonly' => true,
                ],
                'forum' => [
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true,
                ],
                'topic' => [
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true,
                ],
            ],
            'default_status' => 'published',
            'viewless'       => true,
        ];
    }
}

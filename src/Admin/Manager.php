<?php

namespace Bolt\Extension\Bolt\BoltBB\Admin;

use Bolt\Translation\Translator as Trans;
use Silex\Application;

/**
 * BoltBB administration functions
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
class Manager
{
    /** @var Application */
    private $app;
    /** @var array */
    private $config;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['boltbb.config'];
    }

    /**
     * Return an associative array of all of our forums in the database and config
     *
     * @return array
     */
    public function getForums()
    {
        $data = new Data($this->app);

        // Fourm config
        $conf = $this->config['forums'];

        // Defaults for return array
        $return = [
            'needsync' => false,
            'hasrows'  => false,
            'forums'   => $conf,
        ];

        // Database rows
        try {
            $hastopics = false;
            $hasreplies = false;

            // Check to see if the site admin has created our table
            $sm = $this->app['db']->getSchemaManager();
            if (! $sm->tablesExist([$this->config['tables']['forums']])) {
                return $return;
            }

            if ($sm->tablesExist([$this->config['tables']['topics']])) {
                $hastopics = true;
            }

            if ($sm->tablesExist([$this->config['tables']['replies']])) {
                $hasreplies = true;
            }

            $rows = $this->app['db']->fetchAll('SELECT * FROM ' . $this->config['tables']['forums']);

            // Format an array of return values with details fo the forums
            foreach ($rows as $row) {
                $slug = $row['slug'];

                $return['forums'][$slug] = [
                    'title'       => isset($conf[$slug]) ? $conf[$slug]['title'] : $slug,
                    'description' => $conf[$slug]['description'],
                    'subscribers' => empty($row['subscribers']) ? '' : json_decode($row['subscribers'], true),
                    'state'       => isset($conf[$slug]) ? $row['state'] : 'abandoned',
                    'topics'      => $hastopics ? $data->getForumTopicCount($row['id']) : 0,
                    'replies'     => $hasreplies ? $data->getForumReplyCount($row['id']) : 0,
                ];

                // Not enough forums to warrant the extra if()
                $return['hasrows'] = true;
            }

            // If any of the forums in the config file are not in the database, set
            // a flag in the return parameters
            foreach ($conf as $key => $value) {
                if (! isset($return['forums'][$key]['state'])) {
                    $return['needsync'] = true;
                }
            }
        } catch (\Exception $e) {
        }

        return $return;
    }

    /**
     * Check and create forum table records to match the configuration
     *
     * TODO: expand to (maybe) do removes too...  think more first
     *
     * @return void
     */
    public function syncForumDbTables()
    {
        foreach ($this->config['forums'] as $key => $values) {
            // doCreateForumRecord() will only create a forum record if it
            // currently doesn't exist, so just call it
            $this->doCreateForumRecord($key);
        }
    }

    /**
     * Set a particular forum state to 'open'
     *
     * @param int $forum
     */
    public function doForumOpen($forum)
    {
        $this->app['db']->update(
            $this->config['tables']['forums'],
            ['state' => 'open'],
            ['slug'  => $forum]
        );
    }

    /**
     * Set a particular forum state to 'closed'
     *
     * @param int $forum
     */
    public function doForumClose($forum)
    {
        $this->app['db']->update(
            $this->config['tables']['forums'],
            ['state' => 'closed'],
            ['slug'  => $forum]
        );
    }

    /**
     * For each topic, set the topics' replies to have the same forum ID
     *
     * @return void
     */
    public function doRepairReplyRelationships()
    {
        $topics = $this->app['db']->fetchAll('SELECT id, forum from ' . $this->config['tables']['topics']);

        if (empty($topics)) {
            return false;
        }

        foreach ($topics as $topic) {
            $this->app['db']->update(
                $this->config['tables']['replies'],
                ['forum' => $topic['forum']],
                ['topic' => $topic['id']]
            );
        }
    }

    /**
     * For each topic, set the topics' replies to have the same forum ID
     *
     * @return void
     */
    public function doRepairReplyMeta()
    {
        $topics = $this->app['db']->fetchAll('SELECT id, title, forum from ' . $this->config['tables']['topics']);

        if (empty($topics)) {
            return false;
        }

        foreach ($topics as $topic) {
            $i = 1;
            $this->app['db']->update(
                $this->config['tables']['replies'],
                [
                    'title' => '[' . Trans::__('Reply') . ']: ' . $topic['title'],
                    'slug'  => substr($this->app['slugify']->slugify($topic['title']), 0, 120) . '-' . $i,
                ],
                ['topic' => $topic['id']]
            );
            $i++;
        }
    }

    /**
     * Compose and send a test notification
     */
    public function doTestNotification()
    {
        // Get some random default values from our good friends at http://loripsum.net/
        $params = 'medium/decorate/link/ol/ul/dl/bq/code/headers/3';

        $values = [
            'slug'        => '',
            'title'       => trim(strip_tags($this->app['prefill']->get('1/veryshort'))),
            'author'      => 1,
            'authorip'    => '',
            'forum'       => 1,
            'state'       => 'open',
            'visibility'  => 'normal',
            'body'        => trim($this->app['prefill']->get($params)),
            'subscribers' => '',
        ];

        // Create and fill a record object
        $record = $this->app['storage']->getEmptyContent($this->config['contenttypes']['topics']);
        $record->setValues($values);

        // Ensure during this instance we're in debug mode!
        $this->config['notifications']['debug'] = true;

        // Create the notification object
        $notify = new Notifications($this->app, $record);

        // Send it!
        $notify->doNotification();
    }

    /**
     * Create a forum database entry
     *
     * @param string $forum The YAML key for the new forum
     */
    private function doCreateForumRecord($forum)
    {
        $data = new Data($this->app);

        // Check we don't already have an existing record
        $record = $data->getForum($forum);

        if (empty($record)) {
            // Default data for the new forum record
            $data = [
                'slug'  => $forum,
                'state' => 'open',
            ];

            $this->app['db']->insert($this->config['tables']['forums'], $data);
        }
    }
}

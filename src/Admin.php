<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

class Admin
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
     * Return an associative array of all of our forums in the database and config
     *
     * @return mixed
     */
    public function getForums()
    {
        $data = new Data($this->app);

        $conf = $this->config['forums'];
        $rows = $this->app['db']->fetchAll('SELECT * FROM ' . $this->config['tables']['forums']);
        $return = array(
            'needsync' => false,
            'forums' => $conf
        );

        // Format an array of return values with details fo the forums
        foreach ($rows as $row) {
            $slug = $row['slug'];

            $return['forums'][$slug] = array(
                'title'       => isset($conf[$slug]) ? $conf[$slug]['title'] : $slug,
                'description' => $conf[$slug]['description'],
                'subscribers' => empty($row['subscribers']) ? '' : json_decode($row['subscribers'], true),
                'state'       => isset($conf[$slug]) ? $row['state'] : 'abandoned',
                'topics'      => $data->getForumTopicCount($row['id']),
                'replies'     => $data->getForumReplyCount($row['id'])
            );
        }

        // If any of the forums in the config file are not in the database, set
        // a flag in the return parameters
        foreach ($conf as $key => $value) {
            if (! isset($return['forums'][$key]['state'])) {
                $return['needsync'] = true;
            }
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
            array('state' => 'open'),
            array('slug' => $forum)
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
            array('state' => 'closed'),
            array('slug' => $forum)
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
                array('forum' => $topic['forum']),
                array('topic' => $topic['id'])
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
                array(
                    'title' => '[' . __('Reply') . ']:' . $topic['title'],
                    'slug' => makeSlug($topic['title'], 128) . '-' . $i
                ),
                array('topic' => $topic['id'])
            );
            $i++;
        }
    }

    /**
     * Create a forum database entry
     *
     * @param string $forum The YAML key for the new forum
     */
    private function doCreateForumRecord($forum)
    {
        $data = new Data($this->app);

        if (empty($data->getForum($forum))) {
            //
            $data = array(
                'slug'  => $forum,
                'state' => 'open'
            );

            $this->app['db']->insert($this->config['tables']['forums'], $data);
        }
    }
}

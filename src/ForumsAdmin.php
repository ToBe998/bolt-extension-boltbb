<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

class ForumsAdmin
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

        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $this->forums_table_name = $prefix . 'forums';
        $this->topics_table_name = $prefix . $this->config['contenttypes']['topics'];
        $this->replies_table_name = $prefix . $this->config['contenttypes']['replies'];
    }

    /**
     * Return an associative array of all of our forums in the database and config
     *
     * @return mixed
     */
    public function getForums()
    {
        $forums = new Forums($this->app);

        $conf = $this->config['forums'];
        $rows = $this->app['db']->fetchAll('SELECT * FROM ' . $this->forums_table_name);
        $return = array(
            'needsync' => false,
            'forums' => array()
        );

        // Format an array of return values with details fo the forums
        foreach ($rows as $row) {
            $slug = $row['slug'];

            $return['forums'][$slug] = array(
                'title'       => isset($conf[$slug]) ? $conf[$slug]['title'] : $slug,
                'description' => $conf[$slug]['description'],
                'subscribers' => empty($row['subscribers']) ? '' : json_decode($row['subscribers'], true),
                'state'       => isset($conf[$slug]) ? $row['state'] : 'abandoned',
                'topics'      => $forums->getForumTopicCount($row['id']),
                'replies'     => $forums->getForumReplyCount($row['id'])
            );
        }

        // If any of the forums in the config file are not in the database, set
        // a flag in the return parameters
        foreach ($conf as $key => $value) {
            if (! isset($return['forums'][$key])) {
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
     * Create a forum database entry
     *
     * @param string $forum The YAML key for the new forum
     */
    private function doCreateForumRecord($forum)
    {
        if (empty($this->getForum($forum))) {
            //
            $data = array(
                'slug'  => $forum,
                'state' => 'open'
            );

            $this->app['db']->insert($this->forums_table_name, $data);
        }
    }
}

<?php
// Simple Bulletin Board extension for Bolt

namespace Bolt\Extension\Bolt\BoltBB;

// Database access
use Doctrine\DBAL\Schema\Schema;

// Cron
use Bolt\CronEvents;

class Extension extends \Bolt\BaseExtension
{
    /**
     * @var Extension name
     */
    const NAME = 'BoltBB';

    public function getName()
    {
        return Extension::NAME;
    }

    /**
     *
     */
    public function initialize()
    {
        /*
         * Config
         */
        $this->setConfig();

        $this->functions = new Functions($this->app);

        /*
         * Backend
         */
        if ($this->app['config']->getWhichEnd() == 'backend') {
            // Check the database table is up and working
            $this->dbCheck();

            // Create the admin page and routes
            $this->setControllerBackend();
        }

        /*
         * Frontend
         */
        if ($this->app['config']->getWhichEnd() == 'frontend') {

            // Set up routes
            $this->setControllerFrontend();

            // Twig functions
            $this->app['twig']->addExtension(new ForumsTwigExtension($this->app));
        }

        /*
         * Scheduled cron listener
         */
        $this->app['dispatcher']->addListener(CronEvents::CRON_DAILY, array($this, 'cronDaily'));
    }

    /**
     * Cron jobs
     */
    public function cronDaily()
    {
    }

    /**
     * Set up config and defaults
     */
    private function setConfig()
    {
        if (empty($this->config['base_uri'])) {
            $this->config['base_uri'] = 'forums';
        }
        if (empty($this->config['csrf'])) {
            $this->config['csrf'] = true;
        }

        // CSS
        if (isset($this->config['stylesheet'])) {
            $this->config['stylesheet'] = 'css/' . $this->config['stylesheet'];
        } else {
            $this->config['stylesheet'] = 'css/boltbb.min.css';
        }

        // JS
        if (isset($this->config['javascript'])) {
            $this->config['javascript'] = 'js/' . $this->config['javascript'];
        } else {
            $this->config['javascript'] = 'js/boltbb.min.js';
        }
    }

    /**
     * Create controller and define routes
     */
    private function setControllerFrontend()
    {
        $this->controller = new Controllers\Frontend($this->app);

        /*
         * Routes for forum base, individual forums and individual topics
        */
        $this->app->get("/{$this->config['base_uri']}/", array($this->controller, 'Index'))
                    ->before(array($this->controller, 'before'))
                    ->bind('Index');
        $this->app->get("/{$this->config['base_uri']}/all/", array($this->controller, 'Uncategorised'))
                    ->before(array($this->controller, 'before'))
                    ->bind('Uncategorised');
        $this->app->match("/{$this->config['base_uri']}/{forum}/", array($this->controller, 'Forum'))
                    ->before(array($this->controller, 'before'))
                    ->assert('forum', '[a-zA-Z0-9_\-]+')
                    ->bind('Forum')
                    ->method('GET|POST');
        $this->app->match("/{$this->config['base_uri']}/{forum}/{topic}", array($this->controller, 'Topic'))
                    ->before(array($this->controller, 'before'))
                    ->assert('forum', '[a-zA-Z0-9_\-]+')
                    ->assert('topic', '[a-zA-Z0-9_\-]+')
                    ->bind('Topic')
                    ->method('GET|POST');
    }

    /**
     * Create admin controller and define routes
     */
    private function setControllerBackend()
    {
        // check if user has allowed role(s)
        $user    = $this->app['users']->getCurrentUser();
        $userid  = $user['id'];

        foreach ($this->config['admin_roles'] as $role) {
            if ($this->app['users']->hasRole($userid, $role)) {
                $this->authorized = true;
                break;
            }
        }

        if ($this->authorized)
        {
            $this->admin = new Controllers\Backend($this->app);

            $this->path = $this->app['config']->get('general/branding/path') . '/extensions/boltbb';
            $this->app->match($this->path, array($this->admin, 'adminBoltBB'));

            $this->addMenuOption(__('BoltBB'), $this->app['paths']['bolt'] . 'extensions/boltbb', "fa fa-cog");
        }
    }

    /**
     * Register, setup and index our database table
     *
     * @since 1.0
     *
     */
    private function dbCheck()
    {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $me = $this;

        $this->forums_table_name = $prefix . 'forums';
        $this->app['integritychecker']->registerExtensionTable(
            function(Schema $schema) use ($me) {
                // Define table
                $table = $schema->createTable($me->forums_table_name);

                // Add primary column
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));

                // Add working columns
                $table->addColumn("slug", "string", array("length" => 256, "default" => ""));
                $table->addColumn("state", "string", array("length" => 32, "default" => "open"));
                $table->addColumn("subscribers", "string", array("length" => 2048, "default" => null));

                // Index column(s)
                $table->addIndex(array('subscribers'));

                return $table;
            });
    }

    /**
     * Default config options
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array(
            'admin_roles' => array('root', 'admin', 'developer', 'chief-editor')
        );
    }
}

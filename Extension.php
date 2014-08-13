<?php
// Simple Bulletin Board extension for Bolt

namespace BoltBB;

require_once dirname(__FILE__) . '/src/Controller.php';
require_once dirname(__FILE__) . '/src/Functions.php';
require_once dirname(__FILE__) . '/src/ForumsTwigExtension.php';

// Database access
use Doctrine\DBAL\Schema\Schema;

class Extension extends \Bolt\BaseExtension
{
    public function getName()
    {
        return "BoltBB";
    }

    public function initialize()
    {
        if (! isset($this->config['base_uri'])) {
            $this->base_uri = 'forums';
        }

        // CSS
        if (isset($this->config['stylesheet'])) {
            $this->config['stylesheet'] = $this->app['paths']['extensions'] . $this->namespace . '/css/' . $this->config['stylesheet'];
        } else {
            $this->config['stylesheet'] = $this->app['paths']['extensions'] . $this->namespace . '/css/BoltBB.css';
        }

        // Check the database table is up and working
        $this->dbRegister();

        $this->functions = new Functions($this->app, $this->config);
        $this->controller = new Controller($this->app, $this->config, $this->functions);

        /*
         * Routes for forum base, individual forums and individual topics
         */
        $this->app->get("/{$this->config['base_uri']}/", array($this->controller, 'Index'))
                  ->bind('Index');
        $this->app->get("/{$this->config['base_uri']}/all/", array($this->controller, 'Uncategorised'))
                  ->bind('Uncategorised');
        $this->app->match("/{$this->config['base_uri']}/{forum}/", array($this->controller, 'Forum'))
                  ->assert('forum', '[a-zA-Z0-9_\-]+')
                  ->bind('Forum')
                  ->method('GET|POST');
        $this->app->match("/{$this->config['base_uri']}/{forum}/{topic}", array($this->controller, 'Topic'))
                  ->assert('forum', '[a-zA-Z0-9_\-]+')
                  ->assert('topic', '[a-zA-Z0-9_\-]+')
                  ->bind('Topic')
                  ->method('GET|POST');

        // Twig functions
        $this->app['twig']->addExtension(new ForumsTwigExtension($this->functions));
    }

    /**
     * Register, setup and index our database table
     *
     * @since 1.0
     *
     */
    private function dbRegister()
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
                $table->addColumn("subscribers", "string", array("length" => 2048, "default" => null));

                // Index column(s)
                $table->addIndex(array('subscribers'));

                return $table;
            });
    }

}

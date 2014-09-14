<?php
// Simple Bulletin Board extension for Bolt

namespace Bolt\Extension\Bolt\BoltBB;

use Doctrine\DBAL\Schema\Schema;
use Bolt\CronEvents;
use Bolt\StorageEvents;

/**
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 */
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
            $this->app['twig']->addExtension(new BoltBBTwigExtension($this->app));
        }

        /*
         * Scheduled cron listener
         */
        $this->app['dispatcher']->addListener(CronEvents::CRON_DAILY, array($this, 'cronDaily'));

        /*
         * Post-save hook for topic and reply creations
         */
        $this->app['dispatcher']->addListener(StorageEvents::POST_SAVE, array($this, 'hookPostSave'));
    }

    /**
     * Cron jobs
     */
    public function cronDaily()
    {
    }

    /**
     * Post-save hook for topic and reply creations
     *
     * @param \Bolt\StorageEvent $event
     */
    public function hookPostSave(\Bolt\StorageEvent $event)
    {
        // Get contenttype
        $contenttype = $event->getContentType();
        if (empty($contenttype) || !(
            $contenttype == 'topics' ||
            $contenttype == 'replies')) {
                return;
            }

        // If this is not a create event, leave
        if ($event->isCreate()) {
            // Get the newly saved record
            $record = $event->getContent();

            // Launch the notification
            $notify = new Notifications($this->app, $record);
            $notify->doNotification($record);
        }
    }

    /**
     * Set up config and defaults
     */
    private function setConfig()
    {
        // Database table names
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $this->config['tables']['forums'] = $prefix . 'forums';
        $this->config['tables']['topics'] = $prefix . $this->config['contenttypes']['topics'];
        $this->config['tables']['replies'] = $prefix . $this->config['contenttypes']['replies'];

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
        $this->controller = new Controller\Frontend($this->app);

        /*
         * Routes for forum base, individual forums and individual topics
         */
        $this->app->get("/{$this->config['base_uri']}/", array($this->controller, 'index'))
                    ->before(array($this->controller, 'before'))
                    ->bind('index');
        $this->app->get("/{$this->config['base_uri']}/all/", array($this->controller, 'all'))
                    ->before(array($this->controller, 'before'))
                    ->bind('all');
        $this->app->match("/{$this->config['base_uri']}/{forum}/", array($this->controller, 'forum'))
                    ->before(array($this->controller, 'before'))
                    ->assert('forum', '[a-zA-Z0-9_\-]+')
                    ->bind('forum')
                    ->method('GET|POST');
        $this->app->match("/{$this->config['base_uri']}/{forum}/{topic}", array($this->controller, 'topic'))
                    ->before(array($this->controller, 'before'))
                    ->assert('forum', '[a-zA-Z0-9_\-]+')
                    ->assert('topic', '[a-zA-Z0-9_\-]+')
                    ->bind('topic')
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

        if ($this->authorized) {
            $this->controller = new Controller\Backend($this->app);

            $this->path = $this->app['config']->get('general/branding/path') . '/extensions/boltbb';

            // Admin page
            $this->app->match($this->path, array($this->controller, 'admin'))
                      ->before(array($this->controller, 'before'))
                      ->bind('admin')
                      ->method('GET');

            // AJAX requests
            $this->app->match($this->path . '/ajax', array($this->controller, 'ajax'))
                      ->before(array($this->controller, 'before'))
                      ->bind('ajax')
                      ->method('GET|POST');

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
            function (Schema $schema) use ($me) {
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
            'base_uri' => 'forums',
            'webassets' => array(
                'stylesheet' => 'boltbb.min.css',
                'javascript' => 'boltbb.min.js',
            ),
            'contenttypes' => array(
                'topics'  => 'topics',
                'replies' => 'replies'
            ),
            'templates' => array(
                'parent' => 'boltbb.twig',
                'forums' => array(
                    'index' => 'boltbb_index.twig',
                    'forum' => 'boltbb_forum.twig',
                    'topic' => 'boltbb_topic.twig'
                ),
                'navigation' => array(
                    'crumbs' => 'boltbb_crumbs.twig'
                ),
                'email'  => array(
                    'subject' => 'boltbb_email_subject.twig',
                    'body'    => 'boltbb_email_body.twig'
                ),
            ),
            'pagercount' => 5,
            'admin_roles' => array('root', 'admin', 'developer', 'chief-editor'),
            'notifications' => array(
                'debug'         => true,
                'debug_address' => 'noreply@example.com',
                'from_address'  => 'noreply@example.com'
            ),
            'csrf' => true,
            'editor' => array(
                'addons' => array(
                    'images'      => true,
                    'anchor'      => false,
                    'tables'      => true,
                    'fontcolor'   => false,
                    'align'       => false,
                    'subsuper'    => false,
                    'embed'       => true,
                    'codetag'     => false,
                    'codesnippet' => false,
                    'footnotes'   => false
                ),
                'internal' => array(
                    'allowedContent'          => false,
                    'autoParagraph'           => true,
                    'contentsCss'             => array(
                        $this->app['paths']['app'] . 'view/lib/ckeditor/contents.css',
                        $this->app['paths']['app'] . 'view/css/ckeditor.css',
                    )
                )
            )
        );
    }
}

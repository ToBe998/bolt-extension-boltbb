<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Doctrine\DBAL\Schema\Schema;
use Bolt\Events\CronEvent;
use Bolt\Events\CronEvents;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;

/**
 * BoltBB discussion extension for Bolt
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
class Extension extends \Bolt\BaseExtension
{
    /**
     * Extension name
     *
     * @var string
     */
    const NAME = 'BoltBB';

    /**
     * Extension's container
     *
     * @var string
     */
    const CONTAINER = 'extensions.BoltBB';

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
            $this->adminController();
        }

        /*
         * Frontend
         */
        if ($this->app['config']->getWhichEnd() == 'frontend') {

            // Set up controller routes
            $this->app->mount('/' . $this->config['base_uri'], new Controller\BoltBBController());

            // Twig functions
            $this->app['twig']->addExtension(new Twig\BoltBBExtension($this->app));
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
     * @param \Bolt\Events\StorageEvent $event
     */
    public function hookPostSave(StorageEvent $event)
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
            $notify->doNotification();
        }
    }

    /**
     * Conditionally load the admin controller if the user has the valid role
     */
    private function adminController()
    {
        // check if user has allowed role(s)
        $user    = $this->app['users']->getCurrentUser();
        $userid  = $user['id'];

        $this->authorized = false;

        foreach ($this->config['admin_roles'] as $role) {
            if ($this->app['users']->hasRole($userid, $role)) {
                $this->authorized = true;
                break;
            }
        }

        if ($this->authorized) {
            $path = $this->app['config']->get('general/branding/path') . '/extensions/boltbb';
            $this->app->mount($path, new Controller\BoltBBAdminController());
        }
    }

    /**
     * Set up config and defaults
     */
    private function setConfig()
    {
        // Database table names
        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
        $this->config['tables']['forums'] = $prefix . 'forums';
        $this->config['tables']['topics'] = $prefix . $this->config['contenttypes']['topics'];
        $this->config['tables']['replies'] = $prefix . $this->config['contenttypes']['replies'];
    }

    /**
     * Register, setup and index our database table
     *
     * @since 1.0
     *
     */
    private function dbCheck()
    {
        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
        $me = $this;

        $this->forums_table_name = $prefix . 'forums';
        $this->app['integritychecker']->registerExtensionTable(
            function (Schema $schema) use ($me) {
                // Define table
                $table = $schema->createTable($me->forums_table_name);

                // Add primary column
                $table->addColumn('id', 'integer', array('autoincrement' => true));
                $table->setPrimaryKey(array('id'));

                // Add working columns
                $table->addColumn('slug',        'string', array('length' => 256,  'default' => ''));
                $table->addColumn('state',       'string', array('length' => 32,   'default' => 'open'));
                $table->addColumn('subscribers', 'string', array('length' => 2048, 'default' => ''));

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
                    'allowedContent'            => false,
                    'autoParagraph'             => true,
                    'disableNativeSpellChecker' => false,
                    'contentsCss'               => array(
                        $this->app['paths']['app'] . 'view/lib/ckeditor/contents.css',
                        $this->app['paths']['app'] . 'view/css/ckeditor.css',
                    )
                )
            )
        );
    }
}

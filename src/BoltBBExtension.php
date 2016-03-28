<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Bolt\Events\CronEvents;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\Bolt\BoltBB\Provider\BoltBBServiceProvider;
use Bolt\Extension\Bolt\Members\AccessControl\Role;
use Bolt\Extension\Bolt\Members\Event\MembersEvents;
use Bolt\Extension\Bolt\Members\Event\MembersRolesEvent;
use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * BoltBB discussion extension for Bolt
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
class BoltBBExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new BoltBBServiceProvider($this->getConfig())
        ];
    }
    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates/email',
            'templates/forums',
            'templates/navigation',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        $config = $this->getConfig();
        $roles = isset($config['roles']['admin_roles']) ? $config['roles']['admin_roles'] : ['root'];

        return [
            (new MenuEntry('boltbb', 'boltbb'))
                ->setLabel(Trans::__('BoltBB'))
                ->setIcon('fa:pencil-square-o')
                ->setPermission(implode('||', $roles)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();

        return [
            $app['boltbb.config']->getBaseUri() => $app['boltbb.controller.frontend'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        $app = $this->getContainer();

        return [
            '/' => $app['boltbb.controller.backend'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        // Scheduled cron listener
        $dispatcher->addListener(CronEvents::CRON_DAILY, [$this, 'cronDaily']);

        // Post-save hook for topic and reply creations
        $dispatcher->addListener(StorageEvents::POST_SAVE, [$this, 'hookPostSave']);

        // Member roles
        $dispatcher->addListener(MembersEvents::MEMBER_ROLE, [$this, 'addMemberRoles']);
    }

    /**
     * Add our required roles to Members
     *
     * @param MembersRolesEvent $event
     */
    public function addMemberRoles(MembersRolesEvent $event)
    {
        $config = $this->getConfig();

        foreach ($config['roles'] as $role => $name) {
            $role = new Role($role, $name);
            $event->addRole($role);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
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
        if (empty($contenttype)
            || !($contenttype == 'topics' || $contenttype == 'replies')) {
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
     * Default config options
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'base_uri'  => 'forums',
            'webassets' => [
                'stylesheet' => 'boltbb.min.css',
                'javascript' => 'boltbb.min.js',
            ],
            'contenttypes' => [
                'topics'  => 'topics',
                'replies' => 'replies',
            ],
            'templates' => [
                'parent' => 'boltbb.twig',
                'forums' => [
                    'index' => 'boltbb_index.twig',
                    'forum' => 'boltbb_forum.twig',
                    'topic' => 'boltbb_topic.twig',
                ],
                'navigation' => [
                    'crumbs' => 'boltbb_crumbs.twig',
                ],
                'email'  => [
                    'subject' => 'boltbb_email_subject.twig',
                    'body'    => 'boltbb_email_body.twig',
                ],
            ],
            'pagercount'    => 5,
            'admin_roles'   => ['root', 'admin', 'developer', 'chief-editor'],
            'notifications' => [
                'debug'         => true,
                'debug_address' => 'noreply@example.com',
                'from_address'  => 'noreply@example.com',
            ],
            'csrf'   => true,
            'editor' => [
                'addons' => [
                    'images'      => true,
                    'anchor'      => false,
                    'tables'      => true,
                    'fontcolor'   => false,
                    'align'       => false,
                    'subsuper'    => false,
                    'embed'       => true,
                    'codetag'     => false,
                    'codesnippet' => false,
                    'footnotes'   => false,
                ],
                'internal' => [
                    'allowedContent'            => false,
                    'autoParagraph'             => true,
                    'disableNativeSpellChecker' => false,
                    'contentsCss'               => [
                        //$this->app['resources']->getUrl('app') . 'view/css/ckeditor-contents.css',
                        //$this->app['resources']->getUrl('app') . 'view/css/ckeditor.css',
                    ],
                ],
            ],
            'roles' => [
                'boltbb_admin'       => 'BoltBB Admin',
                'boltbb_moderator'   => 'BoltBB Moderator',
                'boltbb_participant' => 'BoltBB Participant',
            ],
        ];
    }
}

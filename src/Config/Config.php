<?php

namespace Bolt\Extension\Bolt\BoltBB\Config;

/**
 * Configuration class
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
class Config
{
    /** @var string */
    protected $baseUri;
    /** @var string */
    protected $contentTypeTopic;
    /** @var string */
    protected $contentTypeReply;
    /** @var boolean */
    protected $csrf;
    /** @var array */
    protected $editorAddons;
    /** @var array */
    protected $editorInternal;
    /** @var boolean */
    protected $notificationsDebug;
    /** @var string */
    protected $notificationsDebugAddress;
    /** @var string */
    protected $notificationsSenderName;
    /** @var string */
    protected $notificationsSenderAddress;
    /** @var integer */
    protected $pagerCount;
    /** @var array */
    protected $rolesAdmin;
    /** @var array */
    protected $rolesUser;
    /** @var string */
    protected $templates;
    /** @var array */
    protected $webAssets;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->baseUri = $config['base_uri'];
        $this->contentTypeTopic = $config['contenttypes']['topics'];
        $this->contentTypeReply = $config['contenttypes']['replies'];
        $this->csrf = $config['csrf'];
        $this->editorAddons = $config['editor']['addons'];
        $this->editorInternal = $config['editor']['internal'];
        $this->notificationsDebug = $config['notifications']['debug'];
        $this->notificationsDebugAddress = $config['notifications']['debug_address'];
        $this->notificationsName = $config['notifications']['from_name'];
        $this->notificationsAddress = $config['notifications']['from_address'];
        $this->pagerCount = $config['pagercount'];
        $this->rolesAdmin = $config['admin_roles'];
        $this->rolesUser = $config['roles'];
        $this->templates = $config['templates'];
        $this->webAssets = $config['webassets'];
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     *
     * @return Config
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentTypeTopic()
    {
        return $this->contentTypeTopic;
    }

    /**
     * @param string $contentTypeTopic
     *
     * @return Config
     */
    public function setContentTypeTopic($contentTypeTopic)
    {
        $this->contentTypeTopic = $contentTypeTopic;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentTypeReply()
    {
        return $this->contentTypeReply;
    }

    /**
     * @param string $contentTypeReply
     *
     * @return Config
     */
    public function setContentTypeReply($contentTypeReply)
    {
        $this->contentTypeReply = $contentTypeReply;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCsrf()
    {
        return $this->csrf;
    }

    /**
     * @param boolean $csrf
     *
     * @return Config
     */
    public function setCsrf($csrf)
    {
        $this->csrf = $csrf;

        return $this;
    }

    /**
     * @return array
     */
    public function getEditorAddons()
    {
        return $this->editorAddons;
    }

    /**
     * @param array $editorAddons
     *
     * @return Config
     */
    public function setEditorAddons(array $editorAddons)
    {
        $this->editorAddons = $editorAddons;

        return $this;
    }

    /**
     * @return array
     */
    public function getEditorInternal()
    {
        return $this->editorInternal;
    }

    /**
     * @param array $editorInternal
     *
     * @return Config
     */
    public function setEditorInternal(array $editorInternal)
    {
        $this->editorInternal = $editorInternal;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNotificationsDebug()
    {
        return $this->notificationsDebug;
    }

    /**
     * @param boolean $notificationsDebug
     *
     * @return Config
     */
    public function setNotificationsDebug($notificationsDebug)
    {
        $this->notificationsDebug = $notificationsDebug;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotificationsDebugAddress()
    {
        return $this->notificationsDebugAddress;
    }

    /**
     * @param string $notificationsDebugAddress
     *
     * @return Config
     */
    public function setNotificationsDebugAddress($notificationsDebugAddress)
    {
        $this->notificationsDebugAddress = $notificationsDebugAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotificationsSenderName()
    {
        return $this->notificationsSenderName;
    }

    /**
     * @param string $notificationsSenderName
     *
     * @return Config
     */
    public function setNotificationsSenderName($notificationsSenderName)
    {
        $this->notificationsSenderName = $notificationsSenderName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotificationsSenderAddress()
    {
        return $this->notificationsSenderAddress;
    }

    /**
     * @param string $notificationsSenderAddress
     *
     * @return Config
     */
    public function setNotificationsSenderAddress($notificationsSenderAddress)
    {
        $this->notificationsSenderAddress = $notificationsSenderAddress;

        return $this;
    }

    /**
     * @return int
     */
    public function getPagerCount()
    {
        return $this->pagerCount;
    }

    /**
     * @param int $pagerCount
     *
     * @return Config
     */
    public function setPagerCount($pagerCount)
    {
        $this->pagerCount = $pagerCount;

        return $this;
    }

    /**
     * @return array
     */
    public function getRolesAdmin()
    {
        return $this->rolesAdmin;
    }

    /**
     * @param array $rolesAdmin
     *
     * @return Config
     */
    public function setRolesAdmin(array $rolesAdmin)
    {
        $this->rolesAdmin = $rolesAdmin;

        return $this;
    }

    /**
     * @return array
     */
    public function getRolesUser()
    {
        return $this->rolesUser;
    }

    /**
     * @param array $rolesUser
     *
     * @return Config
     */
    public function setRolesUser(array $rolesUser)
    {
        $this->rolesUser = $rolesUser;

        return $this;
    }

    /**
     * @param string $parent
     * @param string $key
     *
     * @return string
     */
    public function getTemplate($parent, $key)
    {
        if (!isset($this->templates[$parent][$key])) {
            throw new \BadMethodCallException(sprintf('Template of type "%s" and name of "%s" does not exist in configuration!', $parent, $key));
        }

        return $this->templates[$parent][$key];
    }

    /**
     * @param string $parent
     * @param string $key
     * @param string $template
     *
     * @return Config
     * @internal param string $templates
     *
     */
    public function setTemplate($parent, $key, $template)
    {
        $this->templates[$parent][$key] = $template;

        return $this;
    }

    /**
     * @return array
     */
    public function getWebAssets()
    {
        return $this->webAssets;
    }

    /**
     * @param array $webAssets
     *
     * @return Config
     */
    public function setWebAssets(array $webAssets)
    {
        $this->webAssets = $webAssets;

        return $this;
    }
}

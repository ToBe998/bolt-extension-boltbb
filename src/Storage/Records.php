<?php

namespace Bolt\Extension\Bolt\BoltBB\Storage;

use Bolt\Extension\Bolt\BoltBB\Config\Config;
use Bolt\Storage\Repository\ContentRepository;
use Pimple as Container;

/**
 * Record management for BoltBB
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
class Records
{
    /** @var Config */
    protected $config;
    /** @var Container */
    protected $repos;

    /**
     * Constructor.
     *
     * @param Config    $config
     * @param Container $repos
     */
    public function __construct(Config $config, Container $repos)
    {
        $this->config = $config;
        $this->repos = $repos;
    }

    /**
     * @return ContentRepository`
     */
    protected function getForumsRepository()
    {
        return $this->repos['boltbb_forums'];
    }

    /**
     * @return ContentRepository`
     */
    protected function getTopicsRepository()
    {
        return $this->repos['boltbb_topics'];
    }

    /**
     * @return ContentRepository`
     */
    protected function getRepliesRepository()
    {
        return $this->repos['boltbb_replies'];
    }
}

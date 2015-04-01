<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex\Application;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Default Contenttypes definitions
 *
 * NOTE:
 * Yes this is over-engineered!  It is a placeholder until Bolt catches up with
 * extension defined contenttypes
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
class Contenttypes
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Symfony\Component\Yaml\Parser
     */
    private $parser;

    /**
     * @var array
     */
    private $contenttypes;

    /**
     * Typo avoiding filename of contenttypes.yml
     *
     * @var string
     */
    private $yamlfile = 'contenttypes.yml';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;

        $this->parser = new Parser();
    }

    /**
     * Test to see if a contenttype exists in contenttype.yml
     *
     * @param string $contenttype
     *
     * @return boolean
     */
    public function isContenttype($contenttype)
    {
        if (! isset($this->contenttypes)) {
            $this->loadContenttypesYml();
        }

        if (isset($this->contenttypes[$contenttype])) {
            return true;
        }

        return false;
    }

    /**
     * Load an uncached copy of contenttypes.yml
     *
     * This is overkill in the long-run, but will do until we can dynamically
     * insert contenttypes into $app['config'] early enough to be useful
     */
    private function loadContenttypesYml()
    {
        $filename = $this->app['resources']->getPath('config') . '/' . $this->yamlfile;

        if (is_readable($filename)) {
            $this->contenttypes = $this->parser->parse(file_get_contents($filename) . "\n");
        } else {
            throw new \Exception($filename . ' is not readable!');
        }
    }

    /**
     * Insert a missing contenttype into contenttypes.yml
     *
     * @param string $type Either 'topics' or 'replies'
     *
     * @return void
     */
    public function insertContenttype($type)
    {
        // Check to see if the contenttype is already in contenttypes.yml
        if ($this->isContenttype($type)) {
            return;
        }

        // Build our defaults
        if ($type == 'topics') {
            $output = $this->getDefaultTopics();
        } elseif ($type == 'replies') {
            $output = $this->getDefaultReplies();
        }

        // Get the existing file, comments and all...  Play nice!
        $filename = $this->app['resources']->getPath('config') . '/' . $this->yamlfile;

        if (is_readable($filename)) {
            $data = file_get_contents($filename) . "\n";

            // Append the contenttype
            $data .= "##\n";
            $data .= "## Automatically generated BoltBB contenttype for {$type}\n";
            $data .= "##\n";
            $data .= "{$type}:\n";
            $data .= $this->getYaml($output);

            try {
                file_put_contents($filename, $data . "\n");
            } catch (\Exception $e) {
                throw new \Exception($filename . ' is not writeable!');
            }
        } else {
            throw new \Exception($filename . ' is not readable!');
        }
    }

    /**
     * Work around Symfony YAML's lack of recursion support
     *
     * @param array  $array
     * @param string $out
     *
     * @return string
     */
    private function getYaml($array, $out = '')
    {
        $this->yaml = new Dumper();
        $this->indent = 4;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $out .= str_repeat(" ", $this->indent) . $key . ":\n";
                $out .= $this->yaml->dump($value, 2, $this->indent + 4);
            } else {
                $out .= $this->yaml->dump(array($key => $value), 2, $this->indent);
            }
        }

        return $out;
    }

    /**
     * Setter for topics array
     *
     * @return void
     */
    private function getDefaultTopics()
    {
        return array(
            'name'          => 'Topics',
            'singular_name' => 'Topic',
            'fields'        => array(
                'title' => array(
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic'
                ),
                'body' => array(
                    'type'    => 'html',
                    'height'  => '300px'
                ),
                'author' => array(
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'info'     => 'The ClientLogin ID of the author',
                    'readonly' => true,
                    'group'    => 'Info'
                ),
                'authorip' => array(
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'label'    => 'IP address',
                    'readonly' => true
                ),
                'forum' => array(
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true
                ),
                'state' => array(
                    'type'    => 'select',
                    'info'    => 'Open: Replies can be made<br><br>Closed: Replies are closed',
                    'values'  => array(
                        'open',
                        'closed'
                    ),
                    'variant' => 'inline'
                ),
                'visibility' => array(
                    'type'    => 'select',
                    'variant' => 'inline',
                    'info'    => 'Global: List at top of all forums<br><br>Pinned: List at the top of the specified form (below any global)<br><br>Normal: Listed newest first in the specified forum',
                    'values'  => array(
                        'normal',
                        'pinned',
                        'global'
                    ),
                ),
                'subscribers' => array(
                    'type'     => 'textarea',
                    'readonly' => true,
                    'hidden'   => true
                ),
            ),
            'default_status' => 'published',
        );
    }

    /**
     * Setter for replies array
     *
     * @return void
     */
    private function getDefaultReplies()
    {
        return array(
            'name'          => 'Replies',
            'singular_name' => 'Reply',
            'fields'        => array(
                'title' => array(
                    'type'    => 'text',
                    'class'   => 'large',
                    'group'   => 'topic'
                ),
                'body' => array(
                    'type'    => 'html',
                    'height'  => '300px'
                ),
                'author' => array(
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'info'     => 'The ClientLogin ID of the author',
                    'readonly' => true,
                    'group'    => 'Info'
                ),
                'authorip' => array(
                    'type'     => 'text',
                    'variant'  => 'inline',
                    'label'    => 'IP address',
                    'readonly' => true
                ),
                'forum' => array(
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true
                ),
                'topic' => array(
                    'type'     => 'integer',
                    'variant'  => 'inline',
                    'readonly' => true
                )
            ),
            'default_status' => 'published',
        );
    }
}

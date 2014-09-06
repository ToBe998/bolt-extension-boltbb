<?php

/**
 * Default Contenttypes definitions
 *
 * Yes this is over-engineered!  It is a placeholder until Bolt catches up with
 * extension defined contenttypes
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;
use Silex\Application;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

/**
 * Content override class
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
        $this->config = $this->app['extensions.' . Extension::NAME]->config;

        $this->parser = new Parser();
    }

    /**
     * Test to see if a contenttype exists in contenttype.yml
     *
     * @param string $contenttype
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
     * @param array $array
     * @param string $out
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
            'name' => 'Topics',
            'singular_name' => 'Topic',
            'fields' => array(
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
                    'type'    => 'text',
                    'variant' => 'inline',
                    'info'    => '',
                    'readonly' => true,
                    'group'   => 'Info'
                ),
                'authorip' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'label'   => 'IP address',
                    'readonly' => true
                ),
                'forum' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => true
                ),
                'state' => array(
                    'type'    => 'select',
                    'values'  => array(
                        'open',
                        'closed'
                    ),
                    'variant' => 'inline'
                ),
                'visibility' => array(
                    'type'    => 'select',
                    'values'  => array(
                        'nomal',
                        'pinned',
                        'global'
                    ),
                    'variant' => 'inline'
                ),
                'subscribers' => array(
                    'type' => 'textarea',
                    'readonly' => true,
                    'hidden' => true
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
            'name' => 'Replies',
            'singular_name' => 'Reply',
            'fields' => array(
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
                    'type'    => 'text',
                    'variant' => 'inline',
                    'info'    => '',
                    'readonly' => true,
                    'group'   => 'Info'
                ),
                'authorip' => array(
                    'type'    => 'text',
                    'variant' => 'inline',
                    'label'   => 'IP address',
                    'readonly' => true
                ),
                'forum' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => true
                ),
                'topic' => array(
                    'type'    => 'integer',
                    'variant' => 'inline',
                    'readonly' => true
                )
            ),
            'default_status' => 'published',
        );
    }
}
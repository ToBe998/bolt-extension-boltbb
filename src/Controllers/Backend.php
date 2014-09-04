<?php

namespace Bolt\Extension\Bolt\BoltBB\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;

class Backend
{
    private $app;
    private $functions;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;
        $this->functions = new Functions($this->app);
    }

    /**
     * Controller before render
     */
    public function before()
    {
        // Enable HTML snippets in our routes so that JS & CSS gets inserted
        $this->app['htmlsnippets'] = true;

        // Add our JS & CSS and CKeditor
//         $this->app['extensions.' . Extension::NAME]->addCSS($this->config['stylesheet'] , false);
//         $this->app['extensions']->addJavascript($this->app['paths']['app'] . 'view/lib/ckeditor/ckeditor.js', true);
//         $this->app['extensions.' . Extension::NAME]->addJavascript($this->config['javascript'], true);
    }

    /**
     *
     */
    public function adminBoltBB()
    {
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/assets');
    }

}
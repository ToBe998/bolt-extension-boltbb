<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

/**
 * Notification class
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
class Notifications
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
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $debug_address;

    /**
     * @var string
     */
    private $from_address;

    /**
     * @var \Bolt\Content
     */
    private $record;

    /**
     * @param Silex\Application $app
     * @param \Bolt\Content     $record
     */
    public function __construct(Silex\Application $app, \Bolt\Content $record)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;

        $this->debug = $this->config['notifications']['debug'];
        $this->debug_address = $this->config['notifications']['debug_address'];
        $this->from_address = $this->config['notifications']['from_address'];

        $this->record = $record;
    }

    /**
     *
     */
    public function doNotification()
    {
        // Sort out the "to whom" list
        if ($this->debug) {
            $this->recipients = [
                [
                    'firstName'   => 'Test',
                    'lastName'    => 'Notifier',
                    'displayName' => 'Test Notifier',
                    'email'       => $this->debug_address,
                ], ];
        } else {
            // Get the subscribers to the topic and it's forum
            $subscriptions = new Subscriptions($this->app);
            $this->recipients = $subscriptions->getSubscribers($this->record->values['id']);
        }

        // Get the email template
        $this->doCompose();

        // Get the email template
        foreach ($this->recipients as $recipient) {
            $this->doSend($this->message, $recipient);
        }
    }

    /**
     * Compose the email data to be sent
     */
    private function doCompose()
    {
        // Set our Twig lookup path
        $this->addTwigPath();

        $data = new Data($this->app);
        $forum = $data->getForum($this->record['forum']);

        /*
         * From
         */
        $sender = [
            'from_email' => $this->from_address,
            'from_name'  => isset($this->config['boltbb']['title']) ? $this->config['boltbb']['title'] : 'BoltBB',
        ];

        /*
         * Author information
         */
        if (! isset($this->record->values['authorprofile'])) {
            $this->record->values['authorprofile'] = $this->app['members']->getMember('id', $this->record->values['author']);
        }

        /*
         * Title and slug
         * @TODO Replies are not guaranteed to be on page 1
         */
        if ($this->record->contenttype['slug'] == $this->config['contenttypes']['topics']) {
            $title = $this->record->values['title'];
            $uri = $this->config['base_uri'] . '/' . $forum['slug'] . '/' . $this->record->values['slug'];
        } else {
            $topic = $data->getTopic($this->record->values['topic']);
            $title = $topic->values['title'];
            $uri = $this->config['base_uri'] . '/' . $forum['slug'] . '/' . $topic->values['slug'];
        }

        /*
         * Subject
         */
        $html = $this->app['render']->render($this->config['templates']['email']['subject'], [
            'forum'       => $forum['title'],
            'contenttype' => $this->record->contenttype['singular_name'],
            'title'       => $title,
            'author'      => $this->record->values['authorprofile']['displayname'],
        ]);

        $subject = new \Twig_Markup($html, 'UTF-8');

        /*
         * Body
         */
        $html = $this->app['render']->render($this->config['templates']['email']['body'], [
            'forum'       => $forum['title'],
            'contenttype' => $this->record->contenttype['singular_name'],
            'title'       => $title,
            'author'      => $this->record->values['authorprofile']['displayname'],
            'uri'         => $uri,
            'body'        => $this->record->values['body'],
        ]);

        $body = new \Twig_Markup($html, 'UTF-8');

        /*
         * Build email
         */
        $this->message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject($subject)
                ->setFrom([$sender['from_email'] => $sender['from_name']])
                ->setBody(strip_tags($body))
                ->addPart($body, 'text/html');
    }

    /**
     * Send a notification to a single user
     *
     * @param \Swift_Message $message
     * @param array          $recipient
     */
    private function doSend(\Swift_Message $message, $recipient)
    {
        // Set the recipient for *this* message
        $message->setTo([
            $recipient['email'] => $recipient['displayName'],
        ]);

        if ($this->app['mailer']->send($message)) {
            $this->app['logger.system']->info("Sent BoltBB notification to {$recipient['displayName']} <{$recipient['email']}>", ['event' => 'extensions']);
        } else {
            $this->app['logger.system']->error("Failed BoltBB notification to {$recipient['displayName']} <{$recipient['email']}>", ['event' => 'extensions']);
        }
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/assets/email');
    }
}

<?php

namespace Bolt\Extension\Bolt\BoltBB;

use Silex;

/**
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
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
     *
     * @param Silex\Application $app
     * @param \Bolt\Content     $record
     */
    public function __construct(Silex\Application $app, \Bolt\Content $record)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;

        $this->debug = $this->config['notifications']['debug'];
        $this->debug_address = $this->config['notifications']['debug_address'];
        $this->from_address = $this->config['notifications']['from_address'];

        $this->record = $record;
    }

    /**
     *
     *
     * @since 1.0
     *
     */
    public function doNotification()
    {
        // Sort out the "to whom" list
        if ($this->debug) {
            $this->recipients = array(
                array(
                    'firstName' => 'Test',
                    'lastName' => 'Notifier',
                    'displayName' => 'Test Notifier',
                    'email' => $this->debug_address
                ));

        } else {
            // Get the subscribers to the topic and it's forum
            $subscriptions = new Subscriptions($this->app);
            $this->recipients = $subscriptions->getSubscribers($this->record->values['topic']);
        }

        // Get the email template
        $this->doCompose();

        // Get the email template
        foreach ($this->recipients as $recipient) {
            $this->doSend($this->message, $recipient);
        }
    }

    /**
     *
     */
    private function doCompose()
    {
        // Set our Twig lookup path
        $this->addTwigPath();

        $data = new Data($this->app);
        $forum = $data->getForum($this->record['forum']);

        /*
         * Subject
         */
        $html = $this->app['render']->render($this->config['templates']['email']['subject'], array(
            'forum'       => $forum['title'],
            'contenttype' => $this->record->contenttype['singular_name'],
            'title'       => $this->record->values['title'],
            'author'      => $this->record->values['authorprofile']['displayName']
        ));

        $subject = new \Twig_Markup($html, 'UTF-8');

        // @TODO Replies are not guaranteed to be on page 1
        if ($this->record->contenttype['slug'] == $this->config['contenttypes']['topics']) {
            $uri = $this->config['base_uri'] . '/' . $forum['slug'] . '/' . $this->record->values['slug'];
        } else {
            $uri = $this->config['base_uri'] . '/' . $forum['slug'] . '/' . $this->record->values['slug'];
        }

        /*
         * Body
         */
        $html = $this->app['render']->render($this->config['templates']['email']['body'], array(
            'forum'       => $forum['title'],
            'contenttype' => $this->record->contenttype['singular_name'],
            'title'       => $this->record->values['title'],
            'author'      => $this->record->values['authorprofile']['displayName'],
            'uri'         => $uri,
            'body'        => $this->record->values['body']
        ));

        $body = new \Twig_Markup($html, 'UTF-8');

        /*
         * Build email
         */
        $this->message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($this->from_address)
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
        $message->setTo(array(
            $recipient['email'] => $recipient['displayName']
        ));

        if ($this->app['mailer']->send($message)) {
            $this->app['log']->add("Sent BoltBB notification to {$recipient['displayName']} <{$recipient['email']}>", 3);
        } else {
            $this->app['log']->add("Failed BoltBB notification to {$recipient['displayName']} <{$recipient['email']}>", 3);
        }
    }

    private function addTwigPath()
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/assets/email');
    }
}

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
     *
     * @param Silex\Application $app
     * @param string            $type
     */
    public function __construct(Silex\Application $app, $type)
    {
        $this->app = $app;
        $this->config = $this->app['extensions.' . Extension::NAME]->config;

        $this->debug = $this->config['notifications']['debug'];
        $this->debug_address = $this->config['notifications']['debug_address'];
        $this->from_address = $this->config['notifications']['from_address'];
    }

    /**
     *
     *
     * @since 1.0
     *
     */
    public function doX()
    {
    }

    /**
     *
     *
     * @since 1.0
     *
     */
    public function doY()
    {
    }

    /**
     *
     */
    private function doCompose()
    {
        $this->message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($this->from_address)
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html');
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
        $message->setTo($recipient);

        $res = $this->app['mailer']->send($message);

        // log the result of the attempt
        if ($res) {
            if ($this->debug) {
                $this->app['log']->add('Sent BoltBB notification to '. $formconfig['testmode_recipient'] . ' (in testmode) - ' . $formconfig['recipient_name'], 3);
            } else {
                $this->app['log']->add('Sent BoltBB notification to '. $formconfig['recipient_email'] . ' - ' . $formconfig['recipient_name'], 3);
            }
        }
    }
}

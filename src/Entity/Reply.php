<?php

namespace Bolt\Extension\Bolt\BoltBB\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Reply
{
    /**
     * @Assert\NotBlank()
     */
    protected $body;

    /**
     * @Assert\NotBlank()
     */
    protected $topic;

    /**
     * @Assert\NotBlank()
     */
    protected $author;

    /**
     *
     */
    protected $notify;

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getNotify()
    {
        return $this->notify;
    }

    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

}

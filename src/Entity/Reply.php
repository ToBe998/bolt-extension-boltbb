<?php

namespace Bolt\Extension\Bolt\BoltBB\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Reply
{
    /**
     * @Assert\NotBlank()
     */
    protected $editor;

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

    public function getEditor()
    {
        return $this->editor;
    }

    public function setEditor($editor)
    {
        $this->editor = $editor;
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

<?php

namespace Bolt\Extension\Bolt\BoltBB\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Topic
{
    /**
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @Assert\NotBlank()
     */
    protected $editor;

    /**
     * @Assert\NotBlank()
     */
    protected $forum;

    /**
     * @Assert\NotBlank()
     */
    protected $author;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getEditor()
    {
        return $this->editor;
    }

    public function setEditor($editor)
    {
        $this->editor = $editor;
    }

    public function getForum()
    {
        return $this->forum;
    }

    public function setForum($forum)
    {
        $this->forum = $forum;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

}

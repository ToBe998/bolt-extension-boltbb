<?php

namespace Bolt\Extension\Bolt\BoltBB\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title',  'text')
            ->add('body',   'textarea', array('label' => false,
                                              'attr'  => array('style' => 'height: 150px;')))
            ->add('forum',  'hidden',   array('data'  => $options['data']['forum_id']))
            ->add('author', 'hidden',   array('data'  => $options['data']['author']))
            ->add('post',   'submit',   array('label' => 'Post new topic'));
    }

    public function getName()
    {
        return 'topic';
    }

}

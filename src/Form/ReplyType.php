<?php

namespace Bolt\Extension\Bolt\BoltBB\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('body',   'textarea', array('label' => false,
                                              'attr'  => array('style' => 'height: 150px;')))
            ->add('topic',  'hidden',   array('data'  => $options['data']['topic_id']))
            ->add('author', 'hidden',   array('data'  => $options['data']['author']))
            ->add('notify', 'checkbox', array('label' => 'Notify me of updates to this topic',
                                              'data'  => true,
                                              'required' => false,))
            ->add('post',   'submit',   array('label' => 'Post reply'));
    }

    public function getName()
    {
        return 'reply';
    }

}

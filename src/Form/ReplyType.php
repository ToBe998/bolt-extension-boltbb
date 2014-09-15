<?php

namespace Bolt\Extension\Bolt\BoltBB\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('body',   'textarea', array('label' => false,
                                              'attr'  => array('style' => 'height: 150px;'),
                                              'constraints' => array(
                                                  new Assert\NotBlank(),
                                                  new Assert\Length(array('min' => 2))
                                             )))
            ->add('notify', 'checkbox', array('label' => 'Notify me of updates to this topic',
                                              'data'  => true,
                                              'required' => false,))
            ->add('post',   'submit',   array('label' => 'Post reply'));
    }

    public function getName()
    {
        return 'reply';
    }

//     public function setDefaultOptions(OptionsResolverInterface $resolver)
//     {
//         $resolver->setDefaults(array(
//             'data_class' => 'Bolt\Extension\Bolt\BoltBB\Entity\Reply',
//         ));
//     }

}

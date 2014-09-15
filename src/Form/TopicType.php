<?php

namespace Bolt\Extension\Bolt\BoltBB\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title',  'text',     array('constraints' => array(
                                                  new Assert\NotBlank(),
                                                  new Assert\Length(array('min' => 3))
                                             )))
            ->add('body',   'textarea', array('label' => false,
                                              'attr'  => array('style' => 'height: 150px;'),
                                              'constraints' => array(
                                                  new Assert\NotBlank(),
                                                  new Assert\Length(array('min' => 2))
                                             )))
            ->add('post',   'submit',   array('label' => 'Post new topic'));
    }

    public function getName()
    {
        return 'topic';
    }

//     public function setDefaultOptions(OptionsResolverInterface $resolver)
//     {
//         $resolver->setDefaults(array(
//             'data_class' => 'Bolt\Extension\Bolt\BoltBB\Entity\Topic',
//         ));
//     }

}

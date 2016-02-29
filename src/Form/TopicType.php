<?php

namespace Bolt\Extension\Bolt\BoltBB\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Topic form types
 *
 * Copyright (C) 2014  Gawain Lynch
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
class TopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title',  'text',     ['constraints' => [
                                                  new Assert\NotBlank(),
                                                  new Assert\Length(['min' => 3]),
                                             ]])
            ->add('body',   'textarea', ['label'            => false,
                                              'attr'        => ['style' => 'height: 150px;'],
                                              'constraints' => [
                                                  new Assert\NotBlank(),
                                                  new Assert\Length(['min' => 2]),
                                             ], ])
            ->add('post',   'submit',   ['label' => 'Post new topic']);
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

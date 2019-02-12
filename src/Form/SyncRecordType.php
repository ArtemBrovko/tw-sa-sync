<?php

namespace App\Form;

use App\Entity\SyncRecord;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SyncRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('smartAccountsApiKeyPublic')
            ->add('smartAccountsApiKeyPrivate')
            ->add('transferWiseApiToken')
            ->add('active')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SyncRecord::class,
        ]);
    }
}

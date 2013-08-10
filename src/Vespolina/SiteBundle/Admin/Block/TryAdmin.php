<?php
namespace Vespolina\SiteBundle\Admin\Block;


use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class TryAdmin extends SimpleAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        parent::configureFormFields($formMapper);
        $formMapper
            ->with('form.group_general')
                ->add('url')
            ->end()
        ;

    }

}
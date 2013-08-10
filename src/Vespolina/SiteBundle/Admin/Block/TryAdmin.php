<?php
namespace Vespolina\SiteBundle\Admin\Block;


use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Cmf\Bundle\BlockBundle\Admin\SimpleBlockAdmin;

class TryAdmin extends SimpleBlockAdmin
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
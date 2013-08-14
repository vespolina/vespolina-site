<?php
namespace Vespolina\SiteBundle\Admin\Block;


use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Cmf\Bundle\BlockBundle\Admin\SimpleBlockAdmin;

class SimpleAdmin extends SimpleBlockAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        parent::configureFormFields($formMapper); // TODO: Change the autogenerated stub
        $formMapper->add('content', 'ckeditor');
    }

}
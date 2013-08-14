<?php
namespace Vespolina\SiteBundle\Admin\Content;


use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Cmf\Bundle\ContentBundle\Admin\MultilangStaticContentAdmin;

class StaticAdmin extends MultilangStaticContentAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        parent::configureFormFields($formMapper); // TODO: Change the autogenerated stub
        $formMapper->add('body', 'ckeditor');
    }

}
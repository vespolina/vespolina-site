<?php
namespace Vespolina\SiteBundle\Admin\Blog;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Cmf\Bundle\BlogBundle\Admin\PostAdmin as BasePostAdmin;

class PostAdmin extends BasePostAdmin
{
    protected function configureFormFields(FormMapper $mapper)
    {
        parent::configureFormFields($mapper);
        $mapper->add('body', 'ckeditor');

    }
}
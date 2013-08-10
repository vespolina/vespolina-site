<?php
namespace Vespolina\SiteBundle\Document;


use Symfony\Cmf\Bundle\BlockBundle\Document\MultilangSimpleBlock;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Class TryBlock
 * @package Vespolina\SiteBundle\Document
 *
 * @PHPCRODM\Document(referenceable=true, translator="attribute")
 */
class TryBlock extends MultilangSimpleBlock
{
    /** @PHPCRODM\String(translated=true) */
    protected $url;

    public function getType()
    {
        return 'vespolina_site.block.try';
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

}
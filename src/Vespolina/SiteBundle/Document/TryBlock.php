<?php
/**
 * This file and its content is copyright of Beeldspraak Website Creators BV - (c) Beeldspraak 2012. All rights reserved.
 * Any redistribution or reproduction of part or all of the contents in any form is prohibited.
 * You may not, except with our express written permission, distribute or commercially exploit the content.
 *
 * @author      Beeldspraak <info@beeldspraak.com>
 * @copyright   Copyright 2012, Beeldspraak Website Creators BV
 * @link        http://beeldspraak.com
 *
 */

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
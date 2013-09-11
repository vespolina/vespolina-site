<?php
namespace Vespolina\SiteBundle\Document\Blog;

use Symfony\Cmf\Bundle\BlogBundle\Document\Post as BasePost;

class Post extends BasePost
{
    /**
     * @var string
     */
    private $authorName;

    /**
     * @param string $authorName
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }
}
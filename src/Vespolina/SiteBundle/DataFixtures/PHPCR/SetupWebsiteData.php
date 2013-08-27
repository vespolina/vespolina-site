<?php
namespace Vespolina\SiteBundle\DataFixtures\PHPCR;


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\ChildrenCollection;
use Doctrine\ODM\PHPCR\Document\Generic;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\Util\NodeHelper;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Cmf\Bundle\BlockBundle\Document\BaseBlock;
use Symfony\Cmf\Bundle\BlockBundle\Document\ContainerBlock;
use Symfony\Cmf\Bundle\BlogBundle\Document\Blog;
use Symfony\Cmf\Bundle\BlogBundle\Document\Post;
use Symfony\Cmf\Bundle\ContentBundle\Document\MultilangStaticContent;
use Symfony\Cmf\Bundle\MenuBundle\Document\MultilangMenu;
use Symfony\Cmf\Bundle\MenuBundle\Document\MultilangMenuNode;
use Symfony\Cmf\Bundle\RoutingBundle\Document\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class SetupWebsiteData implements FixtureInterface, ContainerAwareInterface
{
    /** @var DocumentManager */
    protected $dm;

    /** @var ContainerInterface */
    protected $container;

    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    protected $output;

    /** @var \Symfony\Component\Yaml\Yaml */
    protected $yaml;

    /** @var string */
    protected $routeRoot;

    /** @var string */
    protected $contentRoot;

    /** @var string */
    protected $menuRoot;

    /** @var string */
    protected $blogRoot;

    /** @var string */
    protected $defaultLocale;

    /** @var array */
    protected $availableLocales;

    /** @var bool */
    protected $verbose = true;

    /** @var array */
    protected $pages;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $this->yaml = new Yaml();
        $this->pages = array();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        /** @var $manager DocumentManager */
        $this->dm = $manager;

        $this->routeRoot = $this->container->getParameter('cmf_routing.routing_repositoryroot');
        $this->contentRoot = $this->container->getParameter('cmf_routing.content_basepath');
        $this->menuRoot = $this->container->getParameter('cmf_menu.menu_basepath');
        $this->blogRoot = $this->container->getParameter('cmf_blog.blog_basepath');

        $this->defaultLocale = $this->container->getParameter('kernel.default_locale');
        $this->availableLocales = $this->container->getParameter('doctrine_phpcr.odm.locales');

        try {
            $this->createRootNodes();
            $this->loadBasicData();
            $this->loadBlogData();
            $this->loadMenuData();
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Error while loading WebsiteData: %s</error>', $e->getMessage()));
            if ($this->verbose) {
                $this->output->write($e->getTraceAsString());
            }
        }
    }

    /**
     * Creates the root Nodes required for using routes, content and menus
     */
    protected function createRootNodes()
    {
        $this->output->writeln(sprintf('<info>Creating %s, %s, %s and %s</info>', $this->routeRoot, $this->contentRoot, $this->menuRoot, $this->blogRoot));

        NodeHelper::createPath($this->dm->getPhpcrSession(), $this->routeRoot);
        NodeHelper::createPath($this->dm->getPhpcrSession(), $this->contentRoot);
        NodeHelper::createPath($this->dm->getPhpcrSession(), $this->menuRoot);
        NodeHelper::createPath($this->dm->getPhpcrSession(), $this->blogRoot);

        $this->dm->getPhpcrSession()->save();
    }

    /**
     * Loads the basic pages from 01-basic.yml
     * @throws \DomainException
     */
    protected function loadBasicData()
    {
        $contentRoot = $this->dm->find(null, $this->contentRoot);
        $routeRoot = $this->dm->find(null, $this->routeRoot);
        $data = $this->loadYaml('01-basic.yml');

        foreach ($data as $pageName => $pageData) {

            $page = new MultilangStaticContent();
            $page->setName($pageName);
            $page->setLocale($this->defaultLocale);

            if (isset($pageData['parent'])) {
                $parent = isset($this->pages[$pageData['parent']]) ? $this->pages[$pageData['parent']] : null;
                if (!$parent) {
                    throw new \DomainException(sprintf('Parent document %s not found', $pageData['parent']));
                }
                $page->setParent($parent);
            } else {
                $page->setParent($contentRoot);
            }

            $localeData = array();

            // Title
            if (isset($pageData['title'])) {
                if (is_array($pageData['title'])) {
                    foreach ($pageData['title'] as $locale => $title) {
                        $localeData[$locale]['title'] = $title;
                    }
                } else {
                    $page->setTitle($pageData['title']);
                }
            }

            // Body
            if (isset($pageData['body'])) {
                if (is_array($pageData['body'])) {
                    foreach ($pageData['body'] as $locale => $title) {
                        $localeData[$locale]['body'] = $title;
                    }
                } else {
                    $page->setBody($pageData['body']);
                }
            }

            if (isset($pageData['route'])) {

                if (!is_array($pageData['route'])) {
                    $routes = array();
                    foreach (array_keys($this->availableLocales) as $locale) {
                        $routes[$locale] = $pageData['route'];
                    }
                    $pageData['route'] = $routes;
                }

                if (is_array($pageData['route'])) {
                    foreach ($pageData['route'] as $locale => $route) {
                        $route = $this->fixLocaleRoute($locale, $route);
                        $routeObject = new Route();
                        $routeObject->setName(basename($route));
                        $routeObject->setParent($this->getParentRoute($this->routeRoot . $route, true));
                        $routeObject->setRouteContent($page);
                        $routeObject->setRequirement('_locale', $locale);
                        $routeObject->setDefault('_locale', $locale);
                        if (isset($pageData['template'])) {
                            $routeObject->setDefault(RouteObjectInterface::TEMPLATE_NAME, $pageData['template']);
                        }
                        $this->dm->persist($routeObject);
                    }

                }
            }

            if (isset($pageData['additionalInfoBlock'])) {
                $infoBlockData = $pageData['additionalInfoBlock'];
                $infoBlockClass = isset($infoBlockData['type']) ? $infoBlockData['type'] : 'Symfony\Cmf\Bundle\BlockBundle\Document\ContainerBlock';
                /** @var $infoBlock ContainerBlock */
                $infoBlock = new $infoBlockClass();
                $infoBlock->setParentDocument($page);
                $infoBlock->setName('additionalInfoBlock');
                $page->setAdditionalInfoBlock($infoBlock);

                $this->dm->persist($infoBlock);
                $this->dm->flush();

                if (isset($infoBlockData['children'])) {
                     $infoBlock->setChildren($this->processChildren($infoBlock, $infoBlockData['children']));
                }
            }

            $this->dm->persist($page);

            // Set locale based data
            if (count($localeData)) {
                foreach ($localeData as $locale => $data) {
                    foreach ($data as $key => $value) {
                        $method = 'set' . ucfirst($key);
                        $page->{$method}($value);
                    }
                    $this->dm->bindTranslation($page, $locale);
                }
            }

            $this->pages[$page->getName()] = $page;
        }

        $this->dm->flush();
    }

    public function loadBlogData()
    {
        $blogRoot = $this->dm->find(null, $this->blogRoot);
        $data = $this->loadYaml('01-blog.yml');

        foreach ($data as $blogName => $blogData) {
            $blog = new Blog();
            $blog->setName($blogName);
            $blog->setParent($blogRoot);

            $this->dm->persist($blog);

            foreach ($blogData['posts'] as $postData) {
                $post = new Post();
                $post->setBlog($blog);
                $post->setTitle($postData['title']);
                $post->setBody($postData['body']);
                $post->setPublishStartDate(new \DateTime($postData['publishStartDate']));
                $post->setPublishable(true);

                $this->dm->persist($post);
            }
        }

        $this->dm->flush();
    }

    protected function loadMenuData()
    {
        $data = $this->loadYaml('02-menus.yml');

        foreach ($data as $name => $menuData) {
            $label = isset($menuData['label']) ? $menuData['label'] : null;
            $menu = new MultilangMenu();
            $menu->setName($name);
            $menu->setLabel($label);
            $menu->setChildrenAttributes(array('class' => 'nav'));
            $menu->setParent($this->dm->find(null, $this->menuRoot));
            $this->dm->persist($menu);

            $dm = $this->dm;
            $pages = $this->pages;
            $createMenuItems = function($parentItem, $menuData) use($dm, $pages, &$createMenuItems) {
                foreach ($menuData as $itemName => $itemData) {
                    $item = new MultilangMenuNode();
                    $item->setName($itemName);
                    $item->setParent($parentItem);

                    $dm->persist($item);

                    $localeData = array();
                    if (isset($itemData['label'])) {
                        if (is_array($itemData['label'])) {
                            foreach ($itemData['label'] as $locale => $label) {
                                $localeData[$locale]['label'] = $label;
                            }
                        } else {
                            $item->setLabel($itemData['label']);
                        }
                    }

                    if (isset($itemData['content'])) {
                        if (!isset($pages[$itemData['content']])) {
                            throw new \DomainException(sprintf('Content "%s" doesn\'t exists', $itemData['content']));
                        }
                        $item->setContent($pages[$itemData['content']]);
                    }

                    if (isset($itemData['route'])) {
                        $item->setRoute($itemData['route']);
                    }

                    if (isset($itemData['uri'])) {
                        if (is_array($itemData['uri'])) {
                            foreach ($itemData['uri'] as $locale => $uri) {
                                $localeData[$locale]['uri'] = $uri;
                            }
                        } else {
                            $item->setUri($itemData['uri']);
                        }
                    }

                    foreach ($localeData as $locale => $data) {
                        foreach ($data as $key => $value) {
                            $method = 'set' . ucfirst($key);
                            $item->{$method}($value);
                        }
                        $dm->bindTranslation($item, $locale);
                    }

                    if (isset($itemData['children'])) {
                        $createMenuItems($item, $itemData['children']);
                    }
                }
            };

            if (isset($menuData['children'])) {
                $createMenuItems($menu, $menuData['children']);
            }
        }

        $this->dm->flush();
    }

    /**
     * @param $parent
     * @param $childrenData
     * @return ChildrenCollection
     * @throws \DomainException
     */
    protected function processChildren($parent, $childrenData)
    {
        $children = new ChildrenCollection($this->dm, $parent);
        foreach ($childrenData as $childName => $child) {
            $childBlockClass = 'Symfony\Cmf\Bundle\BlockBundle\Document\SimpleBlock';
            if (isset($child['class'])) {
                $childBlockClass = $child['class'];
            }
            /** @var $block BaseBlock */
            $block = new $childBlockClass;
            $block->setName($childName);
            $block->setParentDocument($parent);
            if (isset($child['template'])) {
                $block->getSetting('template', $child['template']);
            }

            $localeData = array();

            if (isset($child['title'])) {
                if (is_array($child['title'])) {
                    if (!$this->dm->isDocumentTranslatable($block)) {
                        throw new \DomainException(sprintf('Block %s isn\'t translatable', $childName));
                    }
                    foreach ($child['title'] as $locale => $title) {
                        $localeData[$locale]['title'] = $title;
                    }
                } else {
                    $block->setTitle($child['title']); // We just hope this block type supports it here ;)
                }
            }

            if (isset($child['content'])) {
                if (is_array($child['content'])) {
                    if (!$this->dm->isDocumentTranslatable($block)) {
                        throw new \DomainException(sprintf('Block %s isn\'t translatable', $childName));
                    }
                    foreach ($child['content'] as $locale => $content) {
                        $localeData[$locale]['content'] = $content;
                    }
                } else {
                    $block->setContent($child['body']); // We just hope this block type supports it here ;)
                }
            }

            if (isset($child['extra'])) {
                foreach ($child['extra'] as $key => $value) {
                    if (is_array($value)) {
                        if (!$this->dm->isDocumentTranslatable($block)) {
                            throw new \DomainException(sprintf('Block %s isn\'t translatable', $childName));
                        }
                        foreach ($value as $locale => $content) {
                            $localeData[$locale][$key] = $content;
                        }
                    } else {
                        $block->{'set' . ucfirst($key)}($value);
                    }
                }
            }

            $this->dm->persist($block);

            foreach ($localeData as $locale => $data) {
                foreach ($data as $key => $value) {
                    $method = 'set' . ucfirst($key);
                    $block->{$method}($value);
                }
                $this->dm->bindTranslation($block, $locale);
            }

            if (isset($child['children'])) {
                $block->setChildren($this->processChildren($block, $child['children']));
            }

            $children->add($block);

        }

        return $children;
    }

    /**
     * @param string $route
     * @param bool $createWhenMissing
     * @return Route
     * @throws \Exception
     */
    protected function getParentRoute($route, $createWhenMissing = false)
    {
        $parentPath = dirname($route);
        $parent = $this->dm->find(null, $parentPath);
        if ($parent) {
            return $parent;
        }

        if (!$createWhenMissing) {
            throw new RouteNotFoundException($parentPath);
        }


        $parentRoute = $this->getParentRoute($parentPath, $createWhenMissing);
        $parent = new Route();
        $parent->setParent($parentRoute);
        $parent->setName(basename($parentPath));
        $this->dm->persist($parent);

        return $route;
    }

    /**
     * @param string $menu
     * @param bool $createWhenMissing
     * @return MultilangMenuNode
     * @throws \DomainException
     */
    protected function getMenuNode($menu, $label = null, $createWhenMissing = false)
    {
        $menuNode = $this->dm->find(null, sprintf('%s/%s', $this->menuRoot, $menu));

        if ($menuNode) {
            return $menuNode;
        }

        if (!$createWhenMissing) {
            throw new \DomainException(sprintf('Menu node %s does nog exists', $menu));
        }

        $menuNode = new MultilangMenuNode();
        $menuNode->setName($menu);
        $menuNode->setParent($this->dm->find(null, $this->menuRoot));
        $this->dm->persist($menuNode);
        if (isset($label)) {
            if (is_array($label)) {
                foreach ($label as $locale => $title) {
                    $menuNode->setLabel($title);
                    $this->dm->bindTranslation($menuNode, $locale);
                }
            } else {
                $menuNode->setLabel($label);
                $this->dm->bindTranslation($label, $this->defaultLocale);
            }
        } else {
            $menuNode->setLabel(ucfirst($menu));
            $this->dm->bindTranslation($menuNode, $this->defaultLocale);
        }

        return $menuNode;
    }

    /**
     * @param $locale string
     * @param $route string
     * @return string
     */
    protected function fixLocaleRoute($locale, $route)
    {
        // Prefix with / when missing
        $route = (0 !== strpos($route, '/')) ? '/' . $route : $route;
        // Check if it starts with locale
        if (substr($route, 0, 3) !== '/' . $locale) {
            if ($route === '/') {
                $route = '/' . $locale;
            } else {
                $route = '/' . $locale . $route;
            }
        }

        return $route;
    }

    /**
     * @param $filename
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function loadYaml($filename)
    {
        try {
            $file = realpath(__DIR__ . '/../../') . '/Resources/data/' . $filename;
            if (!file_exists($file)) {
                throw new \InvalidArgumentException($file . ' could not be found');
            }
            return $this->yaml->parse(file_get_contents($file), true);
        } catch (ParseException $e) {
            $this->output->writeln(sprintf('<error>Error while parsing %s: %s</error>', $filename, $e->getMessage()));
        }
    }

}
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

namespace Vespolina\SiteBundle\Listener;


use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class KernelSubscriber implements EventSubscriberInterface
{
    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    /** @var array */
    private $locales;

    /** @var string */
    private $homeRoute;

    public function __construct(RouterInterface $router, array $locales, $homeRoute)
    {
        $this->router = $router;
        $this->locales = $locales;
        $this->homeRoute = $homeRoute;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('kernelRequest', 20),
        );
    }

    public function kernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ('/' === $request->getPathInfo()) {
            $redirectResponse = new RedirectResponse($this->router->generate($this->homeRoute));
            $event->setResponse($redirectResponse);
        }
    }

}
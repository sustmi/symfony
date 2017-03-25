<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\TypedReference;

/**
 * Compiler pass to register tagged services that require a service locator.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class RegisterServiceSubscribersPass extends AbstractRecursivePass
{
    private $serviceLocator;

    protected function processValue($value, $isRoot = false)
    {
        if ($value instanceof Reference && $this->serviceLocator && 'container' === (string) $value) {
            return new Reference($this->serviceLocator);
        }

        if (!$value instanceof Definition || $value->isAbstract() || $value->isSynthetic() || !$value->hasTag('container.service_subscriber')) {
            return parent::processValue($value, $isRoot);
        }

        $serviceMap = array();

        foreach ($value->getTag('container.service_subscriber') as $attributes) {
            if (!$attributes) {
                continue;
            }
            ksort($attributes);
            if (array() !== array_diff(array_keys($attributes), array('id', 'key'))) {
                throw new InvalidArgumentException(sprintf('The "container.service_subscriber" tag accepts only the "key" and "id" attributes, "%s" given for service "%s".', implode('", "', array_keys($attributes)), $this->currentId));
            }
            if (!array_key_exists('id', $attributes)) {
                throw new InvalidArgumentException(sprintf('Missing "id" attribute on "container.service_subscriber" tag with key="%s" for service "%s".', $attributes['key'], $this->currentId));
            }
            if (!array_key_exists('key', $attributes)) {
                $attributes['key'] = $attributes['id'];
            }
            if (isset($serviceMap[$attributes['key']])) {
                continue;
            }
            $serviceMap[$attributes['key']] = new Reference($attributes['id']);
        }
        $class = $value->getClass();

        if (!is_subclass_of($class, ServiceSubscriberInterface::class)) {
            if (!class_exists($class, false)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $this->currentId));
            }

            throw new InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $this->currentId, ServiceSubscriberInterface::class));
        }
        $this->container->addObjectResource($class);
        $subscriberMap = array();

        foreach ($class::getSubscribedServices() as $key => $type) {
            if (!is_string($type) || !preg_match('/^\??[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+$/', $type)) {
                throw new InvalidArgumentException(sprintf('%s::getSubscribedServices() must return valid PHP types for service "%s" key "%s", "%s" returned.', $class, $this->currentId, $key, is_string($type) ? $type : gettype($type)));
            }
            if ($optionalBehavior = '?' === $type[0]) {
                $type = substr($type, 1);
                $optionalBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
            }
            if (is_int($key)) {
                $key = $type;
            }
            if (!isset($serviceMap[$key])) {
                $serviceMap[$key] = new Reference($type);
            }

            $subscriberMap[$key] = new ServiceClosureArgument(new TypedReference((string) $serviceMap[$key], $type, $optionalBehavior ?: ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE));
            unset($serviceMap[$key]);
        }

        if ($serviceMap = array_keys($serviceMap)) {
            $this->container->log($this, sprintf('Service keys "%s" do not exist in the map returned by %s::getSubscribedServices() for service "%s".', implode('", "', $serviceMap), $class, $this->currentId));
        }

        $serviceLocator = $this->serviceLocator;
        $this->serviceLocator = 'container.'.$this->currentId.'.'.md5(serialize($value));
        $this->container->register($this->serviceLocator, ServiceLocator::class)
            ->addArgument($subscriberMap)
            ->setPublic(false)
            ->setAutowired($value->isAutowired())
            ->addTag('container.service_locator');

        try {
            return parent::processValue($value);
        } finally {
            $this->serviceLocator = $serviceLocator;
        }
    }
}

# TwigBundle

This bundle uses Twig Bridge to integrate Twig templating engine into Symfony Framework. 

## Features
* Provides `@templating.engine.twig` service which enables using `twig` in `framework.templating.engines` configuration.
* Registers `@twig.cache_warmer` and `@twig.template_cache_warmer` services tagged as [`kernel.cache_warmer`](http://symfony.com/doc/current/reference/dic_tags.html#kernel-cache-warmer) in order to automatically warm-up cache for all templates.
* Exposes `debug:twig` and `lint:twig` commands from Twig Bridge.
* Registers `twig.exception_listener` service that listens to `KernelEvents::EXCEPTION` event in order to render error pages for uncaught exceptions. This exception handler is similar to [`ExceptionHandler`](http://symfony.com/doc/current/components/debug.html#enabling-the-exception-handler) from [The Debug Component](http://symfony.com/doc/current/components/debug.html) but it is designed both for production and development environment. Also, it uses Twig templates and provides a way to [customize the error pages](http://symfony.com/doc/current/controller/error_pages.html).
* Provides a [special route](http://symfony.com/doc/current/controller/error_pages.html#testing-error-pages-during-development) that can be used to test error pages. 

## Installation
Before using this bundle in your project, add it to your `composer.json` file:
```
$ composer require symfony/twig-bundle
```

Then, like for any other bundle, include it in your Kernel class:
```php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Symfony\Bundle\TwigBundle\TwigBundle(),
    );

    // ...
}
```

## Configuration
See [TwigBundle Configuration](http://symfony.com/doc/current/reference/configuration/twig.html).

## Resources
  * [Contributing](https://symfony.com/doc/current/contributing/index.html)
  * [Report issues](https://github.com/symfony/symfony/issues) and
    [send Pull Requests](https://github.com/symfony/symfony/pulls)
    in the [main Symfony repository](https://github.com/symfony/symfony)
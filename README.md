Vespolina
=========

This repository hosts the Vespolina website.
For Vespolina documentation, see the https://github.com/vespolina/vespolina-docs repository.

The site is build with Symfony CMF components.

Quick installation instructions
===============================

1. Clone the project from github
2. Copy ``app/config/parameters.yml.dist`` to ``app/config/parameters.yml`` and adjust values to your need
3. Run ``composer install``
4. Create your own _parameters.yml_
5. Create a vhost for your project
6. Execute the follow commands

```
$ php app/console doctrine:database:create
$ php app/console doctrine:phpcr:init:dbal
$ php app/console doctrine:phpcr:register-system-node-types
$ php app/console doctrine:phpcr:fixtures:load
```

If all has gone well, you should now be able to view a homepage. It will most probably redirect you to /en.
Everything below the navigation comes from PHPCR.

A quick layout is build from bootstrap's template 'Justified nav'.
Custom CSS is build with SASS from the VespolinaSiteBundle

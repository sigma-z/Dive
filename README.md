Dive ORM Framework [![Build Status](https://travis-ci.org/sigma-z/Dive.png)](https://travis-ci.org/sigma-z/Dive)
===

Just another ORM implementation... Just another? No, not really...
Okay, it uses active record pattern, as many others too. But there some things are different.

It components are loose coupled, introduced by interfaces, clean, maintainable and extensible.

Concepts behind
---
 * PDO connector based
 * Active record pattern
 * Intelligent relation loading similar to the [NotORM]: https://github.com/vrana/notorm (see also: [Doctrine 2 versus NotORM]: http://www.notorm.com/static/doctrine2-notorm/)
 * Intelligent relation handling
   Two tables with a relation to each other sharing the same relation instance, so both know of each other, which is different to all ORM implementations I know.

Requires
---
 * PHP 5.3 or greater (UnitTests need 5.3.2)
 * Symfony's EventDispatcher (https://github.com/symfony/EventDispatcher)

[On GitHub]: https://github.com/sigma-z/Dive
[Documentation (coming soon)]: http://www.sigma-scripts.de/Dive/docs

Features
---
 * Schema import
 * Schema export
 * Transaction support
 * Event handling
 * Query building
 * Query result hydration
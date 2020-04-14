Dive ORM Framework [![Build Status](https://travis-ci.org/sigma-z/Dive.png)](https://travis-ci.org/sigma-z/Dive)
===

Just another ORM implementation... Just another? No, not really...
Okay, it uses active record pattern, as many others too. But there some things are different.

It components are loose coupled, introduced by interfaces, clean, maintainable and extensible.

See [documentation](http://www.sigma-scripts.de/Dive/docs/index.html) for further details.


Concepts behind
---
 * PDO connector based
 * Active record pattern
 * Intelligent relation loading similar to the [NotORM](https://github.com/vrana/notorm) (see also: [Doctrine 2 versus NotORM](http://www.notorm.com/static/doctrine2-notorm/))
 * Intelligent relation handling
   Two tables with a relation to each other sharing the same relation instance, so both know of each other, which is different to all ORM implementations I know.

Requirements
---
 * PHP 7.1.3 or greater (tested: 5.5, 5.6, 7.0, 7.1, 7.2, 7.3)
 * supported Databases
   * MySql 5.5, 5.6, 5.7
   * MariaDB 10.0, 10.1, 10.2, 10.3, 10.4 
   * SQLite 3
 * [Symfony's EventDispatcher](https://github.com/symfony/EventDispatcher)
 * Important: SQLite 3.8.5 up to 3.8.9 not supported! @see Issue #8
   * PHP versions bundled with incompatible SQLite libraries: 5.5.21 to 25 and 5.6.5 to 9
   * PHP 5.5.26 and 5.6.10 bundle SQLite 3.8.10.2 where that bug is fixed!

Feature list
---
 * Schema import
 * Schema export
 * Transaction support
 * Event handling
 * Query building
 * Query result hydration
 * Behaviors
   * Timestampable
   * Delegate (for implementing [Class Table Inheritance](http://martinfowler.com/eaaCatalog/classTableInheritance.html))

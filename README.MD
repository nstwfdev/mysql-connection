# Reactphp MySQL transactional connection

[![CI](https://img.shields.io/github/actions/workflow/status/nstwfdev/mysql-connection/ci.yml?branch=master&label=ci&logo=github)](https://github.com/nstwfdev/mysql-connection/actions?query=workflow%3Aci+branch%3Amaster)
[![codecov](https://codecov.io/gh/nstwfdev/mysql-connection/branch/master/graph/badge.svg?token=9YL9FSM4RV)](https://codecov.io/gh/nstwfdev/mysql-connection)
[![Packagist Version](https://img.shields.io/packagist/v/nstwf/mysql-connection?logo=packagist)](https://packagist.org/packages/nstwf/mysql-connection)

Simple wrapper of [`ConnectionInterface`](https://github.com/friends-of-reactphp/mysql) that allows you to make transactions easier

> Read [main documentation](https://github.com/friends-of-reactphp/mysql) before

**Table of contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
    * [Factory](#factory)
        * [createConnection()](#createConnection)
    * [ConnectionInterface](#connectioninterface)
        * [query()](#query)
        * [transaction()](#transaction)
        * [begin()](#begin)
        * [commit()](#commit)
        * [rollback()](#rollback)
        * [ping()](#ping)
        * [quit()](#quit)
        * [close()](#close)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Quickstart example

```php
$factory = new \Nstwf\MysqlConnection\Factory\ConnectionFactory(new \React\MySQL\Factory());
$connection = $factory->createConnection('localhost:3306');

$connection
    ->transaction(function (\Nstwf\MysqlConnection\ConnectionInterface $connection) {
        return $connection->query('update users set name = "Tim" where id = 3');
    })
    ->then(function () {
        echo 'OK';
    }, function (\Throwable $throwable) {
        echo $throwable->getMessage();
    });

$connection->quit();
```

## Usage

### Factory

The main role of factory - creating `ConnectionInterface`, by wrapping [`Factory`](https://github.com/friends-of-reactphp/mysql#factory)

#### createConnection

Create connection using [lazy connection](https://github.com/friends-of-reactphp/mysql#createlazyconnection) for future operations.

```php
$factory = new \Nstwf\MysqlConnection\Factory\ConnectionFactory(new \React\MySQL\Factory());
$connection = $factory->createConnection('localhost:3306');
```

### ConnectionInterface

That's a wrapper of original `ConnectionInterface`.

> Currently main difference - wrapper does not support event emitter methods

[See original documentation](https://github.com/friends-of-reactphp/mysql#connectioninterface)

#### query

[See original documentation](https://github.com/friends-of-reactphp/mysql#query)

#### transaction

The `transaction(callable $callable): PromiseInterface` method can be used to perform a transaction.

```php
$connection
    ->transaction(function (\Nstwf\MysqlConnection\ConnectionInterface $connection) {
        return $connection->query('update users set name = "Tim" where id = 3');
    })
    ->then(function () {
        echo 'OK';
    }, function (\Throwable $throwable) {
        echo $throwable->getMessage();
    });
```

Equals to:

```php
$connection
    ->query("BEGIN")
    ->then(
        fn() => $connection->query("COMMIT"),
        function (\Throwable $throwable) use ($connection)  {
        return $connection->query("ROLLBACK")
                          ->then(fn() => $throwable);
        }
    );
```

#### begin

The `begin(): PromiseInterface` method can be used to begin the transaction.

```php
$connection
    ->begin()
    ->then(function () {
        echo 'Begin';
    }, function (\Throwable $throwable) {
        echo $throwable->getMessage();
    });
```

Equals to:

> Sql case-insensitive

```php
$connection->query("BEGIN");
// or
$connection->query("START TRANSACTION");
```

#### commit

The `commit(): PromiseInterface` method can be used to commit the transaction.

```php
$connection
    ->commit()
    ->then(function () use ($connection) {
        echo 'Commit';
    }, function (\Throwable $throwable) {
        echo $throwable->getMessage();
    });
```

Equals to:

> sql case-insensitive

```php
$connection->query("COMMIT");
```

#### rollback

The `rollback(): PromiseInterface` method can be used to rollback the transaction.

```php
$connection
    ->rollback()
    ->then(function () use ($connection) {
        echo 'Rollback';
    }, function (\Throwable $throwable) {
        echo $throwable->getMessage();
    });
```

Equals to:

> Sql case-insensitive

```php
$connection->query("ROLLBACK");
```

#### ping

[See original documentation](https://github.com/friends-of-reactphp/mysql#ping)

#### close

[See original documentation](https://github.com/friends-of-reactphp/mysql#close)

#### quit

[See original documentation](https://github.com/friends-of-reactphp/mysql#quit)

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
composer require nstwf/mysql-connection
```

See also the [CHANGELOG](docs/CHANGELOG.md) for details about version upgrades.

It's *highly recommended to use PHP 8+* * for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
composer install
```

To run the test suite, go to the project root and run:

```bash
vendor/bin/phpunit
```

## License

MIT, see [LICENSE file](LICENSE).

- [friends-of-reactphp/mysql](https://github.com/friends-of-reactphp/mysql) - main project

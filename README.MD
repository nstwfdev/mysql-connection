# Reactphp MySQL transactional connection

[![CI](https://img.shields.io/github/actions/workflow/status/nstwfdev/mysql-connection/ci.yml?branch=master&label=ci&logo=github)](https://github.com/nstwfdev/mysql-connection/actions?query=workflow%3Aci+branch%3Amaster)
[![codecov](https://codecov.io/gh/nstwfdev/mysql-connection/branch/master/graph/badge.svg?token=9YL9FSM4RV)](https://codecov.io/gh/nstwfdev/mysql-connection)
[![Packagist Version](https://img.shields.io/packagist/v/nstwf/mysql-connection?logo=packagist)](https://packagist.org/packages/nstwf/mysql-connection)

Simple wrapper of [connection](https://github.com/friends-of-reactphp/mysql) that allows you to make transactions easier

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

## Quickstart example

```php
$factory = new Nstwf\MysqlConnection\Factory\ConnectionFactory(new React\MySQL\Factory());
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
$factory = new Nstwf\MysqlConnection\Factory\ConnectionFactory(new React\MySQL\Factory());
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

This is equals to:

```php
$connection
    ->query("BEGIN")
    ->then(
        fn() => $connection->query("COMMIT"),
        function (Throwable $throwable) use ($connection)  {
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

This is equals to:

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

This is equals to:

> Sql case-insensitive

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

This is equals to:

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

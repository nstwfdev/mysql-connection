<?php


declare(strict_types=1);


namespace Nstwf\MysqlConnection\Factory;


use Nstwf\MysqlConnection\ConnectionInterface;


interface ConnectionFactoryInterface
{
    /**
     * Create lazy connection from original factory and wrap it
     *
     * ```php
     * $connection = $factory->createConnection('localhost:3306');
     * ```
     *
     * @param $uri
     *
     * @return ConnectionInterface
     */
    public function createConnection(#[\SensitiveParameter] $uri): ConnectionInterface;
}

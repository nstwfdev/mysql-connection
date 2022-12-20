<?php


declare(strict_types=1);


namespace Nstwf\MysqlConnection\Factory;


use Nstwf\MysqlConnection\Connection;
use Nstwf\MysqlConnection\ConnectionInterface;
use React\MySQL\Factory;


final class ConnectionFactory
{
    public function __construct(
        private Factory $factory
    ) {
    }

    public function createConnection(#[\SensitiveParameter] $uri): ConnectionInterface
    {
        $lazyConnection = $this->factory->createLazyConnection($uri);

        return new Connection($lazyConnection);
    }
}

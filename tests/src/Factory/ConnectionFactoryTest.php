<?php


namespace Nstwf\MysqlConnection\Factory;


use Nstwf\MysqlConnection\Connection;
use PHPUnit\Framework\TestCase;
use React\MySQL\ConnectionInterface;
use React\MySQL\Factory;


use function React\Promise\resolve;


class ConnectionFactoryTest extends TestCase
{
    public function testReturnBaseConnectionWithinTransactionalConnection()
    {
        $uri = 'localhost:3306';

        $baseConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();

        $baseFactory = $this->getMockBuilder(Factory::class)->getMock();
        $baseFactory
            ->expects($this->once())
            ->method('createLazyConnection')
            ->with($uri)
            ->willReturn($baseConnection);

        $factory = new ConnectionFactory($baseFactory);

        $this->assertEquals(
            new Connection($baseConnection),
            $factory->createConnection($uri)
        );
    }
}

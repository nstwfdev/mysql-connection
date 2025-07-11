<?php


declare(strict_types=1);


namespace Nstwf\MysqlConnection;


use Nstwf\MysqlConnection\Transaction\State;
use PHPUnit\Framework\TestCase;
use function React\Promise\reject;
use function React\Promise\resolve;


class ConnectionTest extends TestCase
{
    /**
     * @dataProvider beginDataProvider
     */
    public function testBeginWillChangeStateToActive(callable $callable)
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn(resolve());

        $connection = $this->createConnection($baseConnection);

        $callable($connection);

        $this->assertState($connection, State::ACTIVE);
    }

    private function beginDataProvider(): array
    {
        return [
            '"begin" method' => [fn(ConnectionInterface $connection) => $connection->begin()],
            'query with lowercase "begin"' => [fn(ConnectionInterface $connection) => $connection->query('begin')],
            'query with uppercase "BEGIN"' => [fn(ConnectionInterface $connection) => $connection->query('BEGIN')],
            'query with lowercase "start transaction"' => [fn(ConnectionInterface $connection) => $connection->query('start transaction')],
            'query with uppercase "START TRANSACTION"' => [fn(ConnectionInterface $connection) => $connection->query('START TRANSACTION')],
        ];
    }

    public function testBeginWithExceptionChangeStateToIdle()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn(reject());

        $connection = $this->createConnection($baseConnection);

        $connection->begin();

        $this->assertState($connection, State::IDLE);
    }

    public function testBeginTwiceWillThrowException()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn(resolve());

        $connection = $this->createConnection($baseConnection);

        $connection->begin();

        $this->expectException(\Exception::class);
        $connection->begin();
    }

    /**
     * @dataProvider commitDataProvider
     */
    public function testSuccessCommitWillChangeStateToIdle(callable $commitCallable)
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                ['COMMIT']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                resolve()
            );

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $commitCallable($connection);

        $this->assertState($connection, State::IDLE);
    }

    public function testCommitWithoutBeginWillDoNothing()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->never())
            ->method('query');

        $connection = $this->createConnection($baseConnection);

        $connection->commit();

        $this->assertState($connection, State::IDLE);
    }

    public function testCommitWithExceptionWillChangeStateToIdle()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                ['COMMIT']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                reject()
            );

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $connection->commit();

        $this->assertState($connection, State::IDLE);
    }

    private function commitDataProvider(): array
    {
        return [
            '"commit" method' => [fn(ConnectionInterface $connection) => $connection->commit()],
            'query with lowercase "commit"' => [fn(ConnectionInterface $connection) => $connection->query('commit')],
            'query with uppercase "COMMIT"' => [fn(ConnectionInterface $connection) => $connection->query('COMMIT')],
        ];
    }

    /**
     * @dataProvider rollbackDataProvider
     */
    public function testSuccessRollbackWillChangeStateToIdle(callable $rollbackCallable)
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                ['ROLLBACK']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                resolve()
            );

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $rollbackCallable($connection);

        $this->assertState($connection, State::IDLE);
    }

    public function testRollbackWithoutBeginWillDoNothing()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->never())
            ->method('query');

        $connection = $this->createConnection($baseConnection);

        $connection->rollback();

        $this->assertState($connection, State::IDLE);
    }

    public function testRollbackWithExceptionWillChangeStateToIdle()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                ['ROLLBACK']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                reject()
            );

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $connection->rollback();

        $this->assertState($connection, State::IDLE);
    }

    private function rollbackDataProvider(): array
    {
        return [
            '"rollback" method' => [fn(ConnectionInterface $connection) => $connection->rollback()],
            'query with lowercase "rollback"' => [fn(ConnectionInterface $connection) => $connection->query('rollback')],
            'query with uppercase "ROLLBACK"' => [fn(ConnectionInterface $connection) => $connection->query('ROLLBACK')],
        ];
    }

    public function testSuccessTransaction()
    {
        $sql = 'update users set name = "Tim" where id = 3';

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                [$sql],
                ['COMMIT']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                resolve(),
                resolve(),
            );

        $connection = $this->createConnection($baseConnection);

        $connection->transaction(function (ConnectionInterface $connection) use ($sql) {
            return $connection->query($sql);
        });

        $this->assertState($connection, State::IDLE);
    }

    public function testErrorTransaction()
    {
        $sql = 'update users set name = "Tim" where id = 3';
        $exception = new \Exception('Error message');

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ['BEGIN'],
                [$sql],
                ['ROLLBACK']
            )
            ->willReturnOnConsecutiveCalls(
                resolve(),
                reject($exception),
                resolve(),
            );

        $connection = $this->createConnection($baseConnection);

        $promise = $connection->transaction(function (ConnectionInterface $connection) use ($sql) {
            return $connection->query($sql);
        });

        $this->assertState($connection, State::IDLE);
        $this->assertEquals(reject($exception), $promise);
    }

    public function testQueryActuallyCallQuery()
    {
        $sql = 'update users set name = "Tim" where id = 3';

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with()
            ->willReturn(resolve());

        $connection = $this->createConnection($baseConnection);

        $connection->query($sql);
    }

    public function testPingActuallyCallPing()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('ping')
            ->willReturn(resolve());

        $connection = $this->createConnection($baseConnection);

        $connection->ping();
    }

    public function testCloseActuallyCallClose()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('close');

        $connection = $this->createConnection($baseConnection);

        $connection->close();
    }

    public function testQuitActuallyCallClose()
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('quit')
            ->willReturn(resolve());

        $connection = $this->createConnection($baseConnection);

        $connection->quit();
    }

    private function createConnection(\React\MySQL\ConnectionInterface $connection): ConnectionInterface
    {
        return new Connection($connection);
    }

    private function assertState(ConnectionInterface $connection, State $expectedState): void
    {
        $state = new \ReflectionProperty(Connection::class, 'state');
        $this->assertEquals($expectedState, $state->getValue($connection));
    }
}

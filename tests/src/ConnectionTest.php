<?php
declare(strict_types=1);

namespace Nstwf\MysqlConnection;

use Closure;
use Exception;
use Nstwf\MysqlConnection\Transaction\State;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;
use ReflectionProperty;
use function React\Promise\reject;
use function React\Promise\resolve;


class ConnectionTest extends TestCase
{
    #[DataProvider('beginDataProvider')]
    public function testBeginWillChangeStateToActive(Closure $callable): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn($this->createSuccessPromise());

        $connection = $this->createConnection($baseConnection);

        $callable($connection);

        $this->assertState($connection, State::ACTIVE);
    }

    public static function beginDataProvider(): array
    {
        return [
            '"begin" method' => [fn(ConnectionInterface $connection) => $connection->begin()],
            'query with lowercase "begin"' => [fn(ConnectionInterface $connection) => $connection->query('begin')],
            'query with uppercase "BEGIN"' => [fn(ConnectionInterface $connection) => $connection->query('BEGIN')],
            'query with lowercase "start transaction"' => [fn(ConnectionInterface $connection) => $connection->query('start transaction')],
            'query with uppercase "START TRANSACTION"' => [fn(ConnectionInterface $connection) => $connection->query('START TRANSACTION')],
        ];
    }

    public function testBeginWithExceptionChangeStateToIdle(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn($this->createErrorPromise());

        $connection = $this->createConnection($baseConnection);

        $connection->begin();

        $this->assertState($connection, State::IDLE);
    }

    public function testBeginTwiceWillThrowException(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with('BEGIN')
            ->willReturn($this->createSuccessPromise());

        $connection = $this->createConnection($baseConnection);

        $connection->begin();

        $this->expectException(Exception::class);
        $connection->begin();
    }

    #[DataProvider('commitDataProvider')]
    public function testSuccessCommitWillChangeStateToIdle(callable $commitCallable): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) {
                static $i = 0;
                $expected = ['BEGIN', 'COMMIT'][$i] ?? null;
                $i++;

                $this->assertSame(
                    $expected,
                    $sql,
                    sprintf("Expected query '%s', got '%s'", $expected, $sql)
                );

                return $this->createSuccessPromise();
            });

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $commitCallable($connection);

        $this->assertState($connection, State::IDLE);
    }

    public function testCommitWithoutBeginWillDoNothing(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->never())
            ->method('query');

        $connection = $this->createConnection($baseConnection);

        $connection->commit();

        $this->assertState($connection, State::IDLE);
    }

    public function testCommitWithExceptionWillChangeStateToIdle(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) {
                static $i = 0;
                $sequence = ['BEGIN', 'COMMIT'];
                $expected = $sequence[$i] ?? null;
                $i++;

                if ($sql !== $expected) {
                    $this->fail("Expected query '$expected', got '{$sql}'");
                }

                return $i === 1
                    ? $this->createSuccessPromise()
                    : $this->createErrorPromise();
            });


        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $connection->commit();

        $this->assertState($connection, State::IDLE);
    }

    public static function commitDataProvider(): array
    {
        return [
            '"commit" method' => [fn(ConnectionInterface $connection) => $connection->commit()],
            'query with lowercase "commit"' => [fn(ConnectionInterface $connection) => $connection->query('commit')],
            'query with uppercase "COMMIT"' => [fn(ConnectionInterface $connection) => $connection->query('COMMIT')],
        ];
    }

    #[DataProvider('rollbackDataProvider')]
    public function testSuccessRollbackWillChangeStateToIdle(callable $rollbackCallable): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sqlCalled) {
                static $i = 0;
                $sequence = ['BEGIN', 'ROLLBACK'];
                $expected = $sequence[$i] ?? null;
                $i++;

                $this->assertSame($expected, $sqlCalled);

                return $this->createSuccessPromise();
            });

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $rollbackCallable($connection);

        $this->assertState($connection, State::IDLE);
    }

    public function testRollbackWithoutBeginWillDoNothing(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->never())
            ->method('query');

        $connection = $this->createConnection($baseConnection);

        $connection->rollback();

        $this->assertState($connection, State::IDLE);
    }

    public function testRollbackWithExceptionWillChangeStateToIdle(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) {
                static $i = 0;
                $expected = ['BEGIN', 'ROLLBACK'][$i] ?? null;
                $i++;

                $this->assertSame(
                    $expected,
                    $sql,
                    sprintf("Expected query '%s', got '%s'", $expected, $sql)
                );

                return $i === 1
                    ? $this->createSuccessPromise()
                    : $this->createErrorPromise();
            });

        $connection = $this->createConnection($baseConnection);

        $connection->begin();
        $connection->rollback();

        $this->assertState($connection, State::IDLE);
    }

    public static function rollbackDataProvider(): array
    {
        return [
            '"rollback" method' => [fn(ConnectionInterface $connection) => $connection->rollback()],
            'query with lowercase "rollback"' => [fn(ConnectionInterface $connection) => $connection->query('rollback')],
            'query with uppercase "ROLLBACK"' => [fn(ConnectionInterface $connection) => $connection->query('ROLLBACK')],
        ];
    }

    public function testSuccessTransaction(): void
    {
        $sql = 'update users set name = "Tim" where id = 3';

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sqlCalled) use ($sql) {
                static $i = 0;
                $sequence = ['BEGIN', $sql, 'COMMIT'];
                $expected = $sequence[$i] ?? null;
                $i++;

                $this->assertSame($expected, $sqlCalled);

                return $this->createSuccessPromise();
            });

        $connection = $this->createConnection($baseConnection);

        $connection->transaction(function (ConnectionInterface $connection) use ($sql) {
            return $connection->query($sql);
        });

        $this->assertState($connection, State::IDLE);
    }

    public function testErrorTransaction(): void
    {
        $sql = 'update users set name = "Tim" where id = 3';
        $exception = new Exception('Error message');

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sqlCalled) use ($sql, $exception) {
                static $i = 0;
                $sequence = ['BEGIN', $sql, 'ROLLBACK'];
                $expected = $sequence[$i] ?? null;
                $i++;

                $this->assertSame($expected, $sqlCalled);

                return $i === 2
                    ? reject($exception)
                    : $this->createSuccessPromise();
            });

        $connection = $this->createConnection($baseConnection);

        $promise = $connection->transaction(fn(ConnectionInterface $connection) => $connection->query($sql));

        $this->assertState($connection, State::IDLE);
        $this->assertEquals(reject($exception), $promise);
    }

    public function testQueryActuallyCallQuery(): void
    {
        $sql = 'update users set name = "Tim" where id = 3';

        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('query')
            ->with()
            ->willReturn($this->createSuccessPromise());

        $connection = $this->createConnection($baseConnection);

        $connection->query($sql);
    }

    public function testPingActuallyCallPing(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('ping')
            ->willReturn($this->createSuccessPromise());

        $connection = $this->createConnection($baseConnection);

        $connection->ping();
    }

    public function testCloseActuallyCallClose(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('close');

        $connection = $this->createConnection($baseConnection);

        $connection->close();
    }

    public function testQuitActuallyCallClose(): void
    {
        $baseConnection = $this->getMockBuilder(\React\MySQL\ConnectionInterface::class)->getMock();
        $baseConnection
            ->expects($this->once())
            ->method('quit')
            ->willReturn($this->createSuccessPromise());

        $connection = $this->createConnection($baseConnection);

        $connection->quit();
    }

    private function createSuccessPromise(): PromiseInterface
    {
        return resolve(null);
    }

    private function createErrorPromise(string $error = 'Error'): PromiseInterface
    {
        return reject(new Exception($error));
    }

    private function createConnection(\React\MySQL\ConnectionInterface $connection): ConnectionInterface
    {
        return new Connection($connection);
    }

    private function assertState(ConnectionInterface $connection, State $expectedState): void
    {
        $state = new ReflectionProperty(Connection::class, 'state');
        $this->assertEquals($expectedState, $state->getValue($connection));
    }
}

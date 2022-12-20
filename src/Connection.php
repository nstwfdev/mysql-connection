<?php


declare(strict_types=1);


namespace Nstwf\MysqlConnection;


use Nstwf\MysqlConnection\Transaction\State;
use React\MySQL\ConnectionInterface as BaseConnectionInterface;
use React\Promise\PromiseInterface;


use function React\Promise\reject;
use function React\Promise\resolve;


final class Connection implements ConnectionInterface
{
    private State $state = State::IDLE;

    public function __construct(
        private BaseConnectionInterface $connection
    ) {
    }

    public function transaction(callable $callable): PromiseInterface
    {
        return $this
            ->begin()
            ->then(fn() => $callable($this))
            ->then(fn() => $this->commit())
            ->otherwise(function ($error) {
                return $this
                    ->rollback()
                    ->then(fn() => reject($error));
            });
    }

    public function begin(): PromiseInterface
    {
        if ($this->state === State::ACTIVE) {
            throw new \Exception("Already have active transaction");
        }

        $this->state = State::ACTIVE;

        return $this
            ->connection
            ->query("BEGIN")
            ->otherwise(function () {
                $this->state = State::IDLE;
            });
    }

    public function commit(): PromiseInterface
    {
        if ($this->state !== State::ACTIVE) {
            return resolve();
        }

        return $this
            ->connection
            ->query("COMMIT")
            ->always(function () {
                $this->state = State::IDLE;
            });
    }

    public function rollback(): PromiseInterface
    {
        if ($this->state !== State::ACTIVE) {
            return resolve();
        }

        return $this->connection->query("ROLLBACK")
            ->always(function () {
                $this->state = State::IDLE;
            });
    }

    public function query(string $sql, array $params = []): PromiseInterface
    {
        if ($this->matchQuery($sql, ['BEGIN', 'START TRANSACTION'])) {
            return $this->begin();
        }

        if ($this->matchQuery($sql, ['COMMIT'])) {
            return $this->commit();
        }

        if ($this->matchQuery($sql, ['ROLLBACK'])) {
            return $this->rollback();
        }

        return $this
            ->connection
            ->query($sql, $params);
    }

    public function ping(): PromiseInterface
    {
        return $this
            ->connection
            ->ping();
    }

    public function quit(): PromiseInterface
    {
        return $this
            ->connection
            ->quit();
    }

    public function close(): void
    {
        $this
            ->connection
            ->close();
    }

    private function matchQuery(string $sql, array $queries): bool
    {
        return in_array(
            strtoupper($sql),
            array_map(fn(string $query) => strtoupper($query), $queries)
        );
    }
}

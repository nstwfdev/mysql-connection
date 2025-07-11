<?php
declare(strict_types=1);

namespace Nstwf\MysqlConnection;

use Nstwf\MysqlConnection\Exception\ActiveTransactionsExistException;
use React\Promise\PromiseInterface;


interface ConnectionInterface
{
    /**
     * Make a callable in transaction with auto commit/rollback
     *
     * If you want to manually begin and commit/rollback transaction use
     * corresponding methods
     *
     * ```php
     * $connection->transaction(function (ConnectionInterface $connection) {
     *     return $connection->query("update users set is_deleted = 1 where id = 1")
     * });
     * ```
     *
     * @param callable $callable
     *
     * @return PromiseInterface
     */
    public function transaction(callable $callable): PromiseInterface;

    /**
     * Start the transaction
     *
     * Equals to:
     *
     * ```php
     * $connection->query('BEGIN');
     * $connection->query('START TRANSACTION');
     * ```
     *
     * @return PromiseInterface
     * @throws ActiveTransactionsExistException
     */
    public function begin(): PromiseInterface;

    /**
     * Commit the transaction
     *
     * Equals to:
     *
     * ```php
     * $connection->query('COMMIT');
     * ```
     *
     * @return PromiseInterface
     */
    public function commit(): PromiseInterface;

    /**
     * Rollback the transaction
     *
     * Equals to:
     *
     * ```php
     * $connection->query('ROLLBACK');
     * ```
     *
     * @return PromiseInterface
     */
    public function rollback(): PromiseInterface;

    /**
     * If you use `transaction` method - all queries outside transaction will call after transaction resolve
     *
     * @see \React\MySQL\ConnectionInterface::query()
     */
    public function query(string $sql, array $params = []): PromiseInterface;

    /**
     * If you use `transaction` method - all queries outside transaction will call after transaction resolve
     *
     * @see \React\MySQL\ConnectionInterface::ping()
     */
    public function ping(): PromiseInterface;

    /**
     * If you use `transaction` method - all queries outside transaction will call after transaction resolve
     *
     * @see \React\MySQL\ConnectionInterface::quit()
     */
    public function quit(): PromiseInterface;

    /**
     * If you use `transaction` method - all queries outside transaction will call after transaction resolve
     *
     * @see \React\MySQL\ConnectionInterface::close()
     */
    public function close(): void;
}

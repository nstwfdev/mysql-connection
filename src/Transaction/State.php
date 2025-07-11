<?php
declare(strict_types=1);

namespace Nstwf\MysqlConnection\Transaction;

enum State
{
    case ACTIVE;
    case IDLE;
}

<?php

namespace LeoVince\MonologTeams\Tests;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Logger;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getRecord(int|string|Level $level = Level::Warning, string|\Stringable $message = 'test', array $context = [], string $channel = 'test', DateTimeImmutable $datetime = new DateTimeImmutable(), array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: $datetime,
            channel: $channel,
            level: Logger::toMonologLevel($level),
            message: (string) $message,
            context: $context,
            extra: $extra,
        );
    }
}

<?php

use LeoVince\MonologTeams\TeamsHandler;
use LeoVince\MonologTeams\TeamsRecord;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;

test('Can instantiate TeamsHandler class', function () {
    $handler = new TeamsHandler($_ENV['LOG_TEAMS_WEBHOOK_URL']);

    expect($handler)->toBeInstanceOf(TeamsHandler::class)
        ->and($handler->getWebhookUrl())->toBe($_ENV['LOG_TEAMS_WEBHOOK_URL'])
        ->and($handler->getTeamsRecord())->toBeInstanceOf(TeamsRecord::class)
        ->and($handler->getFormatter())->toBeInstanceOf(FormatterInterface::class);
});

test('Can set formatter', function () {
    $handler = new TeamsHandler($_ENV['LOG_TEAMS_WEBHOOK_URL']);

    $lineFormatter = new LineFormatter();
    $handler->setFormatter($lineFormatter);

    expect($handler->getFormatter())->toBe($lineFormatter);
});

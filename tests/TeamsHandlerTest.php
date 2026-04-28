<?php

use LeoVince\MonologTeams\TeamsHandler;
use LeoVince\MonologTeams\TeamsRecord;
use Monolog\Formatter\FormatterInterface;

test('Can instantiate TeamsHandler class', function () {
    $handler = new TeamsHandler($_ENV['TEAMS_WEBHOOK_URL']);

    expect($handler)->toBeInstanceOf(TeamsHandler::class)
        ->and($handler->getWebhookUrl())->toBe($_ENV['TEAMS_WEBHOOK_URL'])
        ->and($handler->getTeamsRecord())->toBeInstanceOf(TeamsRecord::class)
        ->and($handler->getFormatter())->toBeInstanceOf(FormatterInterface::class);
});

test('Can log', function () {
    $handler = new TeamsHandler($_ENV['TEAMS_WEBHOOK_URL']);
    $teamsRecord = $handler->getTeamsRecord();

    $record = $this->getRecord();

    expect($teamsRecord->getTeamsData($record))->toBe([
        'type' => 'message',
        'attachments' => [
            [
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    '$schema' => 'https://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'speak' => 'test',
                    'version' => '1.5',
                    'body' => [
                        [
                            'type' => 'TextBlock',
                            'text' => "**Message**  \ntest",
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Level**  \nWARNING",
                            'wrap' => true,
                        ],
                    ],
                    'msteams' => ['width' => 'full'],
                    'style' => 'warning',
                ],
            ],
        ],
    ]);
});

test('Can log with name', function () {
    $handler = new TeamsHandler($_ENV['TEAMS_WEBHOOK_URL'], 'App Name');
    $teamsRecord = $handler->getTeamsRecord();

    $record = $this->getRecord();

    expect($teamsRecord->getTeamsData($record))->toBe([
        'type' => 'message',
        'attachments' => [
            [
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    '$schema' => 'https://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'speak' => 'test',
                    'version' => '1.5',
                    'body' => [
                        [
                            'type' => 'TextBlock',
                            'text' => "App Name",
                            'isSubtle' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Message**  \ntest",
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Level**  \nWARNING",
                            'wrap' => true,
                        ],
                    ],
                    'msteams' => ['width' => 'full'],
                    'style' => 'warning',
                ],
            ],
        ],
    ]);
});

test('Can log with name, context and extra', function () {
    $handler = new TeamsHandler($_ENV['TEAMS_WEBHOOK_URL'], 'App Name', true);
    $teamsRecord = $handler->getTeamsRecord();

    $record = $this->getRecord(
        context: ['Context' => 'Context value'],
        extra: ['Extra' => 'Extra value'],
    );

    expect($teamsRecord->getTeamsData($record))->toBe([
        'type' => 'message',
        'attachments' => [
            [
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    '$schema' => 'https://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'speak' => 'test',
                    'version' => '1.5',
                    'body' => [
                        [
                            'type' => 'TextBlock',
                            'text' => "App Name",
                            'isSubtle' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Message**  \ntest",
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Level**  \nWARNING",
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Extra**  \nExtra value",
                            'wrap' => true,
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => "**Context**  \nContext value",
                            'wrap' => true,
                        ],
                    ],
                    'msteams' => ['width' => 'full'],
                    'style' => 'warning',
                ],
            ],
        ],
    ]);
});
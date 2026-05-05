<?php

use LeoVince\MonologTeams\TeamsRecord;
use Monolog\Level;

test('Can instantiate TeamsRecord class', function () {
    $teamsRecord = new TeamsRecord();

    expect($teamsRecord)->toBeInstanceOf(TeamsRecord::class);
});

test('Can log', function () {
    $teamsRecord = new TeamsRecord();
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
    $teamsRecord = new TeamsRecord('App Name');
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
    $teamsRecord = new TeamsRecord('App Name', true);

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

test('Stringify', function (mixed $value, string $expectedResult) {
    $teamsRecord = new TeamsRecord();

    expect($teamsRecord->stringify($value))->toBe($expectedResult);
})->with(static function (): array {
    $multipleDimensions = [[1, 2]];
    $numericKeys = ['library' => 'monolog'];
    $singleDimension = [1, 'Hello', 'Jordi'];

    return [
        [[], '[]'],
        [$multipleDimensions, json_encode($multipleDimensions, JSON_PRETTY_PRINT)],
        [$numericKeys, json_encode($numericKeys, JSON_PRETTY_PRINT)],
        [$singleDimension, json_encode($singleDimension)],
        ['Simple string', 'Simple string'],
        [null, 'NULL'],
        [100, '100'],
        [200.20, '200.2'],
        [false, 'FALSE'],
        [true, 'TRUE'],
    ];
});

test('Context has Exception', function () {
    $exception = new Exception('This is an exception');
    $record = $this->getRecord(Level::Critical, 'This is a critical message.', ['exception' => $exception]);

    $teamsRecord = new TeamsRecord(null, true);
    $data = $teamsRecord->getTeamsData($record);

    expect($data['attachments'][0]['content']['body'][2]['text'])
        ->toContain('**Exception**')
        ->toContain('"class": "Exception",')
        ->toContain('"message": "This is an exception",')
        ->toContain('"trace": [');
});

test('Include context and extra using setter', function () {
    $teamsRecord = new TeamsRecord();
    
    $result = $teamsRecord->includeContextAndExtra(true);
    
    expect($result)->toBe($teamsRecord);
    
    $record = $this->getRecord(
        context: ['Context' => 'Context value'],
        extra: ['Extra' => 'Extra value']
    );
    
    $data = $teamsRecord->getTeamsData($record);

    $data = $teamsRecord->getTeamsData($record);
    $bodyTexts = array_column($data['attachments'][0]['content']['body'], 'text');
    $combinedText = implode(' ', $bodyTexts);

    expect($combinedText)
        ->toContain('**Context**')
        ->toContain('Context value')
        ->toContain('**Extra**')
        ->toContain('Extra value');
});

test('Exclude fields using setter', function () {
    $teamsRecord = new TeamsRecord(null, true);
    
    $result = $teamsRecord->excludeFields(['extra.Removed']);
    
    expect($result)->toBe($teamsRecord);
    
    $record = $this->getRecord(
        extra: ['Kept' => 'included', 'Removed' => 'excluded']
    );
    
    $data = $teamsRecord->getTeamsData($record);
    $bodyTexts = array_column($data['attachments'][0]['content']['body'], 'text');
    
    expect(implode('', $bodyTexts))
        ->toContain('included')
        ->not->toContain('excluded');
});

test('Can exclude high and low-level fields', function () {
    $teamsRecord = new TeamsRecord(null, true, ['extra.HighRemoved','extra.Nested.Removed', 'context.HighRemoved', 'context.Nested.Removed']);

    $record = $this->getRecord(
        context: [
            'HighKeep' => 'should appear context',
            'HighRemoved' => 'removed context',
            'Nested' => [
                'Kept' => 'should appear context low',
                'Removed' => 'removed context low',
            ],
        ],
        extra: [
            'HighKeep' => 'should appear extra',
            'HighRemoved' => 'removed extra',
            'Nested' => [
                'Kept' => 'should appear extra low',
                'Removed' => 'removed extra low',
                'Password' => 'removed extra low',
            ],
        ]
    );
    
    $data = $teamsRecord->getTeamsData($record);
    $bodyTexts = array_column($data['attachments'][0]['content']['body'], 'text');
    
    expect(implode(' ', $bodyTexts))
        ->toContain('**Highkeep**')
        ->toContain('should appear context')
        ->toContain('should appear extra')
        ->toContain('**Nested**')
        ->toContain('Kept')
        ->toContain('should appear context low')
        ->toContain('should appear extra low')
        ->not->toContain('**Highremoved**')
        ->not->toContain('removed context')
        ->not->toContain('removed context low')
        ->not->toContain('removed extra')
        ->not->toContain('removed extra low');
});


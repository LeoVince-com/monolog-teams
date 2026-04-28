<?php

namespace LeoVince\MonologTeams;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;

class TeamsRecord
{
    /**
     * Normalizer formatter instance.
     */
    private NormalizerFormatter $normalizerFormatter;

    public function __construct(
        private ?string $name = null,
        private bool $includeContextAndExtra = false,
        private array $excludeFields = [],
        private ?FormatterInterface $formatter = null,
    ) {
        $this->normalizerFormatter = new NormalizerFormatter();
    }

    /**
     * Returns the data formatted according to the Teams webhook requirements.
     */
    public function getTeamsData(LogRecord $record): array
    {
        if ($this->formatter) {
            $message = $this->formatter->format($record);
        } else {
            $message = $record->message;
        }

        $body = [];

        if ($this->name) {
            $body[] = [
                'type' => 'TextBlock',
                'text' => $this->name,
                'isSubtle' => true,
            ];
        }

        $body[] = $this->generateBodySection('Message', $message);
        $body[] = $this->generateBodySection('Level', $record->level->getName());

        if ($this->includeContextAndExtra) {
            $recordData = $this->removeExcludedFields($record);

            foreach (['extra', 'context'] as $key) {
                if (! isset($recordData[$key]) || count($recordData[$key]) === 0) {
                    continue;
                }

                foreach ($recordData[$key] as $title => $value) {
                    $body[] = $this->generateBodySection($title, $value);
                }
            }
        }

        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'content' => [
                        '$schema' => 'https://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'speak' => $message,
                        'version' => '1.5',
                        'body' => $body,
                        'msteams' => ['width' => 'full'],
                        'style' => $this->getStyle($record->level),
                    ],
                ],
            ],
        ];
    }

    /**
     * Name shown at the top of the card.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function includeContextAndExtra(bool $includeContextAndExtra): self
    {
        $this->includeContextAndExtra = $includeContextAndExtra;

        return $this;
    }

    /**
     * Set fields to exclude from the log record.
     */
    public function excludeFields(array $excludeFields): self
    {
        $this->excludeFields = $excludeFields;

        return $this;
    }

    /**
     * @return $this
     */
    public function setFormatter(?FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Generate body section for Teams card.
     */
    private function generateBodySection(string $title, mixed $value): array
    {
        $title = mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');

        $value = is_scalar($value) ? $value : $this->stringify($value);
        $value = str_replace("\n", "  \n", $value);

        return [
            'type' => 'TextBlock',
            'text' => "**{$title}**  \n" . $value,
            'wrap' => true,
        ];
    }

    /**
     * Stringifies an array of key/value pairs to be used in attachment fields
     */
    public function stringify(array $fields): string
    {
        /** @var array<array<mixed>|bool|float|int|string|null> $normalized */
        $normalized = $this->normalizerFormatter->normalizeValue($fields);

        $hasSecondDimension = count(array_filter($normalized, 'is_array')) > 0;
        $hasOnlyNonNumericKeys = count(array_filter(array_keys($normalized), 'is_numeric')) === 0;

        return $hasSecondDimension || $hasOnlyNonNumericKeys
            ? Utils::jsonEncode($normalized, JSON_PRETTY_PRINT|Utils::DEFAULT_JSON_FLAGS)
            : Utils::jsonEncode($normalized, Utils::DEFAULT_JSON_FLAGS);
    }

    /**
     * Get style for the card based on the log level.
     */
    private function getStyle(Level $level): string
    {
        return match ($level) {
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => 'attention',
            Level::Notice, Level::Warning => 'warning',
            Level::Debug, Level::Info => 'accent',
        };
    }

    /**
     * Get a copy of record with fields excluded according to $this->excludeFields.
     */
    private function removeExcludedFields(LogRecord $record): array
    {
        $recordData = $record->toArray();

        foreach ($this->excludeFields as $field) {
            $keys = explode('.', $field);
            $node = &$recordData;
            $lastKey = end($keys);
            foreach ($keys as $key) {
                if (!isset($node[$key])) {
                    break;
                }
                if ($lastKey === $key) {
                    unset($node[$key]);
                    break;
                }
                $node = &$node[$key];
            }
        }

        return $recordData;
    }
}

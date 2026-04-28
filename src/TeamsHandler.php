<?php

namespace LeoVince\MonologTeams;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\Curl\Util as Curl;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;

class TeamsHandler extends AbstractProcessingHandler
{
    /**
     * Teams record instance.
     */
    private TeamsRecord $teamsRecord;

    /**
     * Teams handler constructor.
     */
    public function __construct(
        private readonly string $webhookUrl,
        ?string $name = null,
        bool $includeContextAndExtra = false,
        Level $level = Level::Critical,
        bool $bubble = true,
        array $excludeFields = []
    ) {
        parent::__construct($level, $bubble);

        $this->teamsRecord = new TeamsRecord($name, $includeContextAndExtra, $excludeFields);
    }

    public function getTeamsRecord(): TeamsRecord
    {
        return $this->teamsRecord;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        $postData = $this->teamsRecord->getTeamsData($record);
        $postString = Utils::jsonEncode($postData);

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->webhookUrl,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POSTFIELDS => $postString,
        ];

        curl_setopt_array($ch, $options);

        Curl::execute($ch);
    }

    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        parent::setFormatter($formatter);
        $this->teamsRecord->setFormatter($formatter);

        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        $formatter = parent::getFormatter();
        $this->teamsRecord->setFormatter($formatter);

        return $formatter;
    }
}

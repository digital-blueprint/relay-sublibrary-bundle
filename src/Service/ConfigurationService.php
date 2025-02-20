<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

class ConfigurationService
{
    private array $config = [];

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * The REST API endpoint to use.
     */
    public function getApiUrl(): string
    {
        return $this->config['api_url'];
    }

    /**
     * The API key for the REST API.
     */
    public function getApiKey(): string
    {
        return $this->config['api_key'];
    }

    /**
     * The API key for the analytics API.
     */
    public function getAnalyticsApiKey(): string
    {
        return $this->config['analytics_api_key'];
    }

    public function isReadOnly(): bool
    {
        return $this->config['readonly'];
    }

    /**
     * Returns the pull path for a specific report type.
     *
     * @param $reportType - the report type identifier
     */
    public function getAnalyticsReportPath(string $reportType): string
    {
        $reportTypes = $this->config['analytics_reports'];
        if (!array_key_exists($reportType, $reportTypes)) {
            throw new \RuntimeException('Unknown report: '.$reportType);
        }

        return $reportTypes[$reportType];
    }
}

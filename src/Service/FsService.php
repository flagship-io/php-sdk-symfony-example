<?php

namespace App\Service;

use Flagship\Config\FlagshipConfig;
use Flagship\Enum\CacheStrategy;
use Flagship\Flagship;
use Flagship\Visitor\VisitorInterface;
use Psr\Log\LoggerInterface;

class FsService
{
    private string $fsEnvId;
    private string $fsApiKey;
    private LoggerInterface $logger;

    public function __construct(string $fsEnvId, string $fsApiKey, LoggerInterface $logger)
    {

        $this->fsEnvId = $fsEnvId;
        $this->fsApiKey = $fsApiKey;
        $this->logger = $logger;
    }

    public function getVisitor(): VisitorInterface
    {
        // start the SDK in Decision API mode
        Flagship::start($this->fsEnvId, $this->fsApiKey,
            FlagshipConfig::decisionApi()
                ->setCacheStrategy(CacheStrategy::BATCHING_AND_CACHING_ON_FAILURE)
                ->setLogManager($this->logger)); // Set to send logs to symfony logger

        //Create a visitor instance
        $visitor = Flagship::newVisitor("visitor")
            ->build();

        //Fetch flags
        $visitor->fetchFlags();

        // Return the visitor instance
        return $visitor;
    }
}
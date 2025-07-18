<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

final readonly class EventClassMap implements EventClassMapInterface
{
    private array $eventMappings;
    private array $aggregateMappings;
    private array $eventToAggregateMap;
    private array $projections;

    public function __construct(KernelInterface $kernel)
    {
        $config = Yaml::parseFile($kernel->getProjectDir().'/config/fluxCapacitor/event_mappings.yaml');
        $this->eventMappings = $config['events'] ?? [];
        $this->aggregateMappings = $config['aggregates'] ?? [];
        $this->eventToAggregateMap = $config['event_to_aggregate'] ?? [];
        $this->projections = $config['projections'] ?? [];
    }

    public function getClassNameByEventPath(string $eventPath): string
    {
        return array_search($eventPath, $this->eventMappings, true) ?: $eventPath;
    }

    public function getEventPathByClassName(string $eventClassName): string
    {
        return $this->eventMappings[$eventClassName] ?? $eventClassName;
    }

    public function getStreamNameByEventPath(string $eventPath): string
    {
        $className = basename(str_replace('\\', '/', $eventPath));
        if (isset($this->eventToAggregateMap[$className])) {
            return $this->eventToAggregateMap[$className];
        }

        $parts = explode('\\', $eventPath);
        foreach ($parts as $part) {
            if (isset($this->aggregateMappings[$part])) {
                return $part;
            }
        }

        return basename(str_replace('\\', '/', $eventPath));
    }

    public function getAggregatePathByByStreamName(string $streamName): string
    {
        return $this->aggregateMappings[$streamName] ?? $streamName;
    }

    public function getClassNamesByEventsPaths(array $eventsPaths): array
    {
        return array_map([$this, 'getClassNameByEventPath'], $eventsPaths);
    }

    public function getEventToAggregateMap(): array
    {
        return $this->eventToAggregateMap;
    }

    public function getProjections(): array
    {
        return $this->projections;
    }
}

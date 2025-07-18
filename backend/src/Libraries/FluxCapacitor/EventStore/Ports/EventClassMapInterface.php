<?php

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface EventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string;

    public function getEventPathByClassName(string $eventClassName): string;

    public function getClassNamesByEventsPaths(array $eventsPaths): array;

    public function getAggregatePathByByStreamName(string $streamName): string;

    public function getStreamNameByEventPath(string $eventPath): string;

    public function getEventToAggregateMap(): array;

    public function getProjections(): array;
}

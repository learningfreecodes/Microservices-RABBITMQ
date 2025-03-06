<?php

namespace Ecommerce\Shared\Events;

abstract class Event
{
    public string $eventName;
    public string $timestamp;
    public array $payload;

    public function __construct(array $payload)
    {
        $this->eventName = static::class;
        $this->timestamp = (new \DateTime())->format('Y-m-d\TH:i:s.u\Z');
        $this->payload = $payload;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'timestamp' => $this->timestamp,
            'payload' => $this->payload
        ];
    }
}
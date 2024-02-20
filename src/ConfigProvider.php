<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [
                ProducerListCommand::class,
                ProducerProduceCommand::class,
                ConsumerListCommand::class
            ],
            'listeners' => [
                AnnotationDefaultListener::class => 9999
            ]
        ];
    }
}

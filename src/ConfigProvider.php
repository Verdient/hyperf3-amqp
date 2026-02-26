<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Listener\MainWorkerStartListener as ListenerMainWorkerStartListener;
use Hyperf\Amqp\Message\ConsumerMessageInterface;
use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Stringable\Str;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

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
            'dependencies' => [
                ListenerMainWorkerStartListener::class => MainWorkerStartListener::class
            ],
            'listeners' => [
                SetAnnotationDefaultValueListener::class => 9999
            ],
            'logger' => [
                ConsumerMessageInterface::class => (function (string $name) {
                    $nameParts = array_map([Str::class, 'kebab'], explode('\\', Utils::simplifyName($name)));

                    $filename = BASE_PATH . '/runtime/logs/consumer/' . implode('/', $nameParts) . '/.log';

                    return [
                        'handler' => [
                            'class' => RotatingFileHandler::class,
                            'constructor' => [
                                'filename' => $filename,
                                'filenameFormat' => '{date}'
                            ],
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'constructor' => [
                                'format' => "%datetime% [%level_name%] %message%\n",
                                'dateFormat' => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks' => true,
                            ],
                        ]
                    ];
                })->bindTo(null),
                ProducerMessageInterface::class => (function (string $name) {
                    $nameParts = array_map([Str::class, 'kebab'], explode('\\', Utils::simplifyName($name)));

                    $filename = BASE_PATH . '/runtime/logs/producer/' . implode('/', $nameParts) . '/.log';

                    return [
                        'handler' => [
                            'class' => RotatingFileHandler::class,
                            'constructor' => [
                                'filename' => $filename,
                                'filenameFormat' => '{date}'
                            ],
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'constructor' => [
                                'format' => "%datetime% [%level_name%] %message%\n",
                                'dateFormat' => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks' => true,
                            ],
                        ]
                    ];
                })->bindTo(null)
            ]
        ];
    }
}

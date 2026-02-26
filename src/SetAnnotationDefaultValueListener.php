<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Stringable\Str;
use Override;
use Verdient\Hyperf3\Di\Container;

use function Hyperf\Support\env;

/**
 * 设置注解默认值监听器
 *
 * @author Verdient。
 */
class SetAnnotationDefaultValueListener implements ListenerInterface
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function process(object $event): void
    {
        $command = $_SERVER['argv'][1] ?? null;

        if ($command === 'server:watch') {
            return;
        }

        if (!$enablerManager = Container::getOrNull(EnablerManager::class)) {
            return;
        }

        $classes = AnnotationCollector::getClassesByAnnotation(Consumer::class);

        foreach ($classes as $class => $annotation) {

            if (is_null($annotation->enable)) {
                $envName = $this->getEnvName($class);
                $enablerManager->collect($class, $envName);
                $annotation->enable = env($envName, true);
            }

            if (empty($annotation->exchange) || empty($annotation->routingKey)) {

                $parts = explode('\\', $class);

                if (count($parts) === 4 && str_starts_with($class, 'App\Amqp\Consumer\\')) {
                    $parts[] = 'Default';
                }

                if (empty($annotation->routingKey)) {
                    $annotation->routingKey = end($parts);
                    if (substr($annotation->routingKey, -8) === 'Consumer') {
                        $annotation->routingKey = substr($annotation->routingKey, 0, -8);
                    }
                } else {
                    end($parts);
                }

                if (empty($annotation->exchange)) {
                    $annotation->exchange = prev($parts);
                }

                if (substr($annotation->exchange, -8) === 'Consumer') {
                    $annotation->exchange = substr($annotation->exchange, 0, -8);
                }
            }

            if (empty($annotation->queue)) {
                $annotation->queue = $annotation->exchange . '-' . $annotation->routingKey;
            }
        }

        $classes = AnnotationCollector::getClassesByAnnotation(Producer::class);

        foreach ($classes as $class => $annotation) {

            if (empty($annotation->exchange) || empty($annotation->routingKey)) {

                $parts = explode('\\', $class);

                if (count($parts) === 4 && str_starts_with($class, 'App\Amqp\Producer\\')) {
                    $parts[] = 'Default';
                }

                if (empty($annotation->routingKey)) {
                    $annotation->routingKey = end($parts);
                    if (substr($annotation->routingKey, -8) === 'Producer') {
                        $annotation->routingKey = substr($annotation->routingKey, 0, -8);
                    }
                } else {
                    end($parts);
                }
                if (empty($annotation->exchange)) {
                    $annotation->exchange = prev($parts);
                }
                if (substr($annotation->exchange, -8) === 'Producer') {
                    $annotation->exchange = substr($annotation->exchange, 0, -8);
                }
            }
        }
    }

    /**
     * 获取环境变量名称
     *
     * @param string $class 类名
     * @author Verdient。
     */
    protected function getEnvName(string $class): string
    {
        return 'CONSUMER_' . strtoupper(implode('_', array_map(function ($part) {
            return Str::snake($part);
        }, explode('\\', $class))));
    }
}

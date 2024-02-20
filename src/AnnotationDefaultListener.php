<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Stringable\Str;

use function Hyperf\Support\env;

/**
 * 消费者进程注册监听器
 * @author Verdient。
 */
class AnnotationDefaultListener implements ListenerInterface
{
    /**
     * @author Verdient。
     */
    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function process(object $event): void
    {
        $classes = AnnotationCollector::getClassesByAnnotation(Consumer::class);

        /** @var EnablerManager */
        $enablerManager = $this->container->get(EnablerManager::class);

        foreach ($classes as $class => $annotation) {

            if (is_null($annotation->enable)) {
                $envName = $this->getEnvName($class);
                $enablerManager->collect($class, $envName);
                $annotation->enable = env($envName, true);
            }

            if (empty($annotation->exchange) || empty($annotation->routingKey)) {
                $parts = explode('\\', $class);
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
            }

            if (empty($annotation->queue)) {
                $annotation->queue = $annotation->exchange . '-' . $annotation->routingKey;
            }
        }

        $classes = AnnotationCollector::getClassesByAnnotation(Producer::class);

        foreach ($classes as $class => $annotation) {

            if (empty($annotation->exchange) || empty($annotation->routingKey)) {
                $parts = explode('\\', $class);
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
            }
        }
    }

    /**
     * 获取环境变量名称
     * @param string $class 类名
     * @return string
     * @author Verdient。
     */
    protected function getEnvName(string $class): string
    {
        return 'CONSUMER_' . strtoupper(implode('_', array_map(function ($part) {
            return Str::snake($part);
        }, explode('\\', $class))));
    }
}

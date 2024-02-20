<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessageInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use phpDocumentor\Reflection\DocBlockFactory;

use function Hyperf\Support\make;

/**
 * 解析消费者
 * @author Verdient。
 */
trait ParseConsumers
{
    /**
     * 解析消费者
     * @author Verdient。
     */
    public function parseConsumers(): array
    {
        $consumers = [];

        $docBlockFactory = DocBlockFactory::createInstance();

        foreach (AnnotationCollector::getClassesByAnnotation(Consumer::class) as $class => $annotation) {
            $instance = make($class);

            if (!$instance instanceof ConsumerMessageInterface) {
                continue;
            }

            $reflectClass = ReflectionManager::reflectClass($class);
            $docComment = $reflectClass->getDocComment();
            $consumers[$class] = [
                'class' => $class,
                'description' => $docComment ? $docBlockFactory->create($docComment)->getSummary() : '',
                'exchange' => $annotation->exchange,
                'routingKey' => $annotation->routingKey,
                'queue' => $annotation->queue,
                'enable' => $annotation->enable,
                'pool' => $annotation->pool
            ];
        }

        ksort($consumers);

        return array_values($consumers);
    }
}

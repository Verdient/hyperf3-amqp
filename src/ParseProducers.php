<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use phpDocumentor\Reflection\DocBlockFactory;

/**
 * 解析生产者
 * @author Verdient。
 */
trait ParseProducers
{
    /**
     * 解析生产者
     * @author Verdient。
     */
    public function parseProducers(): array
    {
        $producers = [];

        $docBlockFactory = DocBlockFactory::createInstance();

        foreach (AnnotationCollector::getClassesByAnnotation(Producer::class) as $class => $annotation) {

            if (!is_subclass_of($class, ProducerMessageInterface::class)) {
                continue;
            }

            $reflectClass = ReflectionManager::reflectClass($class);
            $docComment = $reflectClass->getDocComment();
            $producers[$class] = [
                'class' => $class,
                'description' => $docComment ? $docBlockFactory->create($docComment)->getSummary() : '',
                'exchange' => $annotation->exchange,
                'routingKey' => $annotation->routingKey,
                'pool' => $annotation->pool
            ];
        }

        ksort($producers);

        return array_values($producers);
    }
}

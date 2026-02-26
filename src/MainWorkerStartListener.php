<?php

namespace Verdient\Hyperf3\Amqp;

use Doctrine\Instantiator\Instantiator;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\DeclaredExchanges;
use Hyperf\Amqp\Listener\MainWorkerStartListener as ListenerMainWorkerStartListener;
use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Amqp\Producer as AmqpProducer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Override;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Container\ContainerInterface;
use Throwable;
use Verdient\Hyperf3\Event\Event;
use Verdient\Hyperf3\Exception\ExceptionOccurredEvent;

/**
 * @author Verdient。
 */
class MainWorkerStartListener extends ListenerMainWorkerStartListener
{
    public function __construct(
        protected ContainerInterface $container,
        protected StdoutLoggerInterface $logger
    ) {
        parent::__construct($container, $logger);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function process(object $event): void
    {
        if (!$this->isEnable()) {
            return;
        }

        /** @var array<string,Producer> */
        $producers = AnnotationCollector::getClassesByAnnotation(Producer::class);

        if (empty($producers)) {
            return;
        }

        /** @var AmqpProducer */
        $producer = $this->container->get(AmqpProducer::class);

        /** @var Instantiator */
        $instantiator = $this->container->get(Instantiator::class);

        foreach ($producers as $class => $annotation) {
            $instance = $instantiator->instantiate($class);
            if (!$instance instanceof ProducerMessageInterface) {
                continue;
            }
            $annotation->exchange && $instance->setExchange($annotation->exchange);
            $annotation->routingKey && $instance->setRoutingKey($annotation->routingKey);
            try {
                $producer->declare($instance);
                DeclaredExchanges::add($instance->getExchange());
                $routingKey = $instance->getRoutingKey();
                if (is_array($routingKey)) {
                    $routingKey = implode(',', $routingKey);
                }
                $this->logger->debug(sprintf('AMQP exchange[%s] and routingKey[%s] were created successfully.', $instance->getExchange(), $routingKey));
            } catch (AMQPProtocolChannelException $e) {
                $this->logger->debug('AMQPProtocolChannelException: ' . $e->getMessage());
                DeclaredExchanges::remove($instance->getExchange());
            } catch (Throwable $exception) {
                $this->logger->error((string) $exception);
                Event::dispatch(new ExceptionOccurredEvent($exception));
            }
        }
    }
}

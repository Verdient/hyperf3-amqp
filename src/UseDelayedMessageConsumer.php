<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Builder\QueueBuilder;
use Override;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * 使用延时消息消费者
 *
 * @author Verdient。
 */
trait UseDelayedMessageConsumer
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getQueueBuilder(): QueueBuilder
    {
        return (new QueueBuilder())->setQueue($this->getQueue())
            ->setArguments(new AMQPTable(['x-dead-letter-exchange' => $this->getDeadLetterExchange()]));
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function getDeadLetterExchange(): string
    {
        return 'delayed';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getExchangeBuilder(): ExchangeBuilder
    {
        return (new ExchangeBuilder())->setExchange($this->getExchange())
            ->setType('x-delayed-message')
            ->setArguments(new AMQPTable(['x-delayed-type' => $this->getTypeString()]));
    }
}

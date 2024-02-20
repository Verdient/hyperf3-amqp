<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Builder\QueueBuilder;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * 使用延时消息消费者
 * @method string getQueue()
 * @author Verdient。
 */
trait UseDelayedMessageConsumer
{
    /**
     * Overwrite
     * @author Verdient。
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return (new QueueBuilder())->setQueue($this->getQueue())
            ->setArguments(new AMQPTable(['x-dead-letter-exchange' => $this->getDeadLetterExchange()]));
    }

    /**
     * Overwrite
     * @author Verdient。
     */
    protected function getDeadLetterExchange(): string
    {
        return 'delayed';
    }

    /**
     * Overwrite
     * @author Verdient。
     */
    public function getExchangeBuilder(): ExchangeBuilder
    {
        return (new ExchangeBuilder())->setExchange($this->getExchange())
            ->setType('x-delayed-message')
            ->setArguments(new AMQPTable(['x-delayed-type' => $this->getTypeString()]));
    }
}

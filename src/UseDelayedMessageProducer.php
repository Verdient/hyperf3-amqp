<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * 使用延时消息生产者
 * @method string getExchange()
 * @method string getTypeString()
 * @property array $properties
 * @author Verdient。
 */
trait UseDelayedMessageProducer
{
    /**
     * 延迟的毫秒数
     * @author Verdient。
     */
    protected int $delayMs = 0;

    /**
     * 设置延时的毫秒数
     * @return static
     * @author Verdient。
     */
    public function setDelayMs(int $millisecond, string $name = 'x-delay'): static
    {
        $this->delayMs = $millisecond;
        $this->properties['application_headers'] = new AMQPTable([$name => $millisecond]);
        return $this;
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

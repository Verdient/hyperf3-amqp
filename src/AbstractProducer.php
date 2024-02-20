<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Message\ProducerMessage;

/**
 * 抽象生产者
 * @author Verdient。
 */
abstract class AbstractProducer extends ProducerMessage
{
    /**
     * 失败后的重试次数
     * @author Verdient。
     */
    protected int $numberOfRetries = 0;

    /**
     * 超时时间
     * @author Verdient。
     */
    protected int $timeout = 0;

    /**
     * 延迟阈值
     * @author Verdient。
     */
    protected int $delayThreshold = 0;

    /**
     * 重试模式
     * @author Verdient。
     */
    protected RetryMode $retryMode = RetryMode::REQUEUE;

    /**
     * 设置重试的次数
     * @param int $millisecond 毫秒数
     * @return static
     * @author Verdient。
     */
    public function setNumberOfRetries(int $number, RetryMode $retryMode = RetryMode::REQUEUE): static
    {
        $this->numberOfRetries = $number;
        $this->retryMode = $retryMode;
        return $this;
    }

    /**
     * 设置超时时间
     * @param int $second 超时的秒数
     * @return static
     * @author Verdient。
     */
    public function setTimeout(int $second): static
    {
        $this->timeout = $second;
        return $this;
    }

    /**
     * 设置延迟阈值
     * @param int $second 延迟的秒数
     * @return static
     * @author Verdient。
     */
    public function setDelayThreshold(int $second): static
    {
        $this->delayThreshold = $second;
        return $this;
    }

    /**
     * 设置重试模式
     * @param RetryMode $retryMode 重试模式
     * @return static
     * @author Verdient。
     */
    public function setRetryMode(RetryMode $retryMode): static
    {
        $this->retryMode = $retryMode;
        return $this;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function serialize(): string
    {
        $message = new Message($this->payload);
        $property = 'delayMs';
        if (property_exists($this, $property)) {
            $message->setDelayMs($this->{$property});
        }
        $message->setNumberOfRetries($this->numberOfRetries);
        $message->setTimeout($this->timeout);
        $message->setRetryMode($this->retryMode);
        $message->setDelayThreshold($this->delayThreshold);
        return serialize($message);
    }
}

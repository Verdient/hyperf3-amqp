<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

/**
 * 消息
 * @author Verdient。
 */
class Message
{
    /**
     * @var int 消息编号
     * @author Verdient。
     */
    protected $id;

    /**
     * @var string 消息内容
     * @author Verdient。
     */
    protected $message;

    /**
     * @var float 创建时间
     * @author Verdient。
     */
    protected $createdAt;

    /**
     * @var float 执行时间
     * @author Verdient。
     */
    protected $executionAt;

    /**
     * 失败后的重试次数
     * @author Verdient。
     */
    protected int $numberOfRetries = 0;

    /**
     * 延迟阈值
     * @author Verdient。
     */
    protected int $delayThreshold = 0;

    /**
     * 超时时间
     * @author Verdient。
     */
    protected int $timeout = 0;

    /**
     * 重试模式
     * @author Verdient。
     */
    protected RetryMode $retryMode = RetryMode::REQUEUE;

    /**
     * @param mixed $message 消息内容
     * @author Verdient。
     */
    public function __construct($message)
    {
        $timestamp = microtime(true);
        $this->id = md5(implode('.', [
            serialize($message),
            strval($timestamp),
            str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT)
        ]));
        $this->message = $message;
        $this->createdAt = $timestamp;
        $this->executionAt = $timestamp;
    }

    /**
     * 设置延迟的毫秒
     * @param int $millisecond 毫秒数
     * @return static
     * @author Verdient。
     */
    public function setDelayMs(int $millisecond): static
    {
        $second = $millisecond / 1000;
        $this->executionAt = $this->createdAt + $second;
        return $this;
    }

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
     * 获取编号
     * @return int
     * @author Verdient。
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 获取内容
     * @return string
     * @author Verdient。
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 获取创建时间
     * @return float
     * @author Verdient。
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 获取执行时间
     * @return float
     * @author Verdient。
     */
    public function getExecutionAt()
    {
        return $this->executionAt;
    }

    /**
     * 获取延迟的秒数
     * @return float
     * @author Verdient。
     */
    public function getDelayedSeconds()
    {
        $delaySeconds = microtime(true) - $this->executionAt;
        if ($delaySeconds > 0) {
            return $delaySeconds;
        }
        return 0;
    }

    /**
     * 获取重试的次数
     * @return int
     * @author Verdient。
     */
    public function getNumberOfRetries()
    {
        return $this->numberOfRetries;
    }

    /**
     * 获取重试模式
     * @return RetryMode
     * @author Verdient。
     */
    public function getRetryMode(): RetryMode
    {
        return $this->retryMode;
    }

    /**
     * 获取超时
     * @return int
     * @author Verdient。
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 获取是否已超时
     * @return bool
     * @author Verdient。
     */
    public function getIsTimeout(): bool
    {
        if ($this->timeout > 0) {
            return time() > ($this->executionAt + $this->timeout);
        }
        return false;
    }

    /**
     * 获取延迟阈值
     * @return int
     * @author Verdient。
     */
    public function getDelayThreshold(): int
    {
        return $this->delayThreshold;
    }

    /**
     * 获取是否已延迟
     * @return bool
     * @author Verdient。
     */
    public function getIsDelayed(): bool
    {
        if ($this->delayThreshold > 0) {
            return $this->getDelayedSeconds() > $this->delayThreshold;
        }
        return false;
    }
}

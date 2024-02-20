<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Verdient\cli\Console;
use Verdient\Hyperf3\Exception\ExceptionOccurredEvent;

use function Hyperf\Config\config;

/**
 * 抽象消费者
 * @author Verdient。
 */
abstract class AbstractConsumer extends ConsumerMessage
{
    use HasLogger;

    /**
     * 重试的消息
     * @author Verdient。
     */
    protected array $retries = [];

    /**
     * 是否是Debug模式
     * @author Verdient。
     */
    protected bool|null $isDebug = null;

    /**
     * 获取是否在Debug模式
     * @return bool
     * @author Verdient。
     */
    protected function getIsDebug(): bool
    {
        if ($this->isDebug === null) {
            $this->isDebug = (bool) config('debug', false);
        }
        return $this->isDebug;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        if (!$data instanceof Message) {
            $this->logger()->emergency('Message must instance of ' . Message::class);
            return Result::DROP;
        }

        if ($data->getIsTimeout()) {
            $this->logger()->emergency('Message is timeout');
            return Result::DROP;
        }

        $messageId = $data->getId();

        if (!isset($this->retries[$messageId])) {
            if ($data->getIsDelayed()) {
                $delaySeconds = $data->getDelayedSeconds();
                if ($this->container) {
                    /** @var EventDispatcherInterface */
                    if ($eventDispatcher = $this
                        ->container
                        ->get(EventDispatcherInterface::class)
                    ) {
                        $eventDispatcher->dispatch(new ExceptionOccurredEvent('队列消费延迟超过阈值（' . $data->getDelayThreshold() . 's），当前延迟时间为：' . intval($delaySeconds) . ' s'));
                    }
                }
            }
        }

        while (true) {
            try {
                return parent::consumeMessage($data->getMessage(), $message);
            } catch (\Throwable $e) {
                if ($this->container) {
                    /** @var EventDispatcherInterface */
                    if ($eventDispatcher = $this
                        ->container
                        ->get(EventDispatcherInterface::class)
                    ) {
                        $eventDispatcher->dispatch(new ExceptionOccurredEvent($e));
                    }
                }
                $this->logger()->emergency($e);
                if ($this->getIsDebug()) {
                    Console::error($e->__toString(), Console::FG_RED);
                }
                if ($data->getNumberOfRetries() > 0) {
                    if (!isset($this->retries[$data->getId()])) {
                        $this->retries[$data->getId()] = 1;
                        switch ($data->getRetryMode()) {
                            case RetryMode::IMMEDIATE:
                                $this->logger()->info('立即重试，重试次数：' . $this->retries[$data->getId()]);
                                break;
                            case RetryMode::REQUEUE:
                                $this->logger()->info('重新进入队列，重试次数：' . $this->retries[$data->getId()]);
                                return Result::REQUEUE;
                        }
                    } else {
                        if ($this->retries[$data->getId()] >= $data->getNumberOfRetries()) {
                            unset($this->retries[$data->getId()]);
                            $this->logger()->error('已达到最大重试次数（' . $data->getNumberOfRetries() . '），丢弃消息');
                            return Result::DROP;
                        } else {
                            $this->retries[$data->getId()] += 1;
                            switch ($data->getRetryMode()) {
                                case RetryMode::IMMEDIATE:
                                    $this->logger()->info('立即重试，重试次数：' . $this->retries[$data->getId()]);
                                    break;
                                case RetryMode::REQUEUE:
                                    $this->logger()->info('重新进入队列，重试次数：' . $this->retries[$data->getId()]);
                                    return Result::REQUEUE;
                            }
                        }
                    }
                } else {
                    $this->logger()->error('消息消费失败，丢弃消息');
                    return Result::DROP;
                }
            }
        }
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function unserialize(string $data)
    {
        return unserialize($data);
    }
}

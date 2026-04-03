<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\ConsumerMessageInterface;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Override;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Verdient\Hyperf3\Di\Container;
use Verdient\Hyperf3\Event\Event;
use Verdient\Hyperf3\Exception\ExceptionOccurredEvent;
use Verdient\Hyperf3\Logger\HasLogger;

use function Hyperf\Config\config;

/**
 * 抽象消费者
 *
 * @author Verdient。
 */
abstract class AbstractConsumer extends ConsumerMessage
{
    use HasLogger;

    /**
     * 重试的消息
     *
     * @author Verdient。
     */
    protected array $retries = [];

    /**
     * 是否是Debug模式
     *
     * @author Verdient。
     */
    protected ?bool $isDebug = null;

    /**
     * 获取是否在Debug模式
     *
     * @author Verdient。
     */
    protected function isDebug(): bool
    {
        if ($this->isDebug === null) {
            $this->isDebug = (bool) config('debug', false);
        }

        return $this->isDebug;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isEnable(): bool
    {
        if ($this->enable === false) {
            return false;
        }

        return config('amqp.consumer_enable', $this->enable);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        if (!$data instanceof Message) {
            $this->logger()->emergency('消息必须是 ' . Message::class . ' 的实例');
            return Result::DROP;
        }

        if ($data->getIsTimeout()) {
            $this->logger()->emergency('消息已超时');
            return Result::DROP;
        }

        $messageId = $data->getId();

        if (!isset($this->retries[$messageId])) {
            if ($data->getIsDelayed()) {

                $delaySeconds = $data->getDelayedSeconds();

                if ($this->container) {
                    Event::dispatch(new ExceptionOccurredEvent(
                        new RuntimeException(
                            '队列消费延迟超过阈值（' . $data->getDelayThreshold() . 's），当前延迟时间为：' . intval($delaySeconds) . ' s'
                        )
                    ));
                }
            }
        }

        while (true) {
            try {
                $startAt = microtime(true);

                $result = parent::consumeMessage($data->getMessage(), $message);

                $executionCost = sprintf(
                    '%.4f',
                    microtime(true) - $startAt
                );

                $this->logger()->info('执行结束，结果：' . $result->name . '，耗时 ' . $executionCost . ' 秒。');

                return $result;
            } catch (\Throwable $e) {

                if ($this->container) {
                    Event::dispatch(new ExceptionOccurredEvent($e));
                }

                $this->logger()->emergency($e);

                if ($this->isDebug()) {
                    if ($logger = Container::getOrNull(StdoutLoggerInterface::class)) {
                        $formatter = Container::getOrNull(FormatterInterface::class);
                        $logger->error($formatter ? $formatter->format($e) : $e);
                    }
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
     * @author Verdient。
     */
    #[Override]
    public function unserialize(string $data)
    {
        return unserialize($data);
    }

    /**
     * 创建默认的记录器的组名集合
     *
     * @return array<int|string,string>
     * @author Verdient。
     */
    protected function groupsForCreateDefaultLogger(): array
    {
        return [static::class => ConsumerMessageInterface::class];
    }
}

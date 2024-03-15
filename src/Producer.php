<?php

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Amqp\Producer as AmqpProducer;
use Hyperf\Context\ApplicationContext;

/**
 * 生产者
 * @author Verdient。
 */
class Producer
{
    /**
     * 生产消息
     * @param ProducerMessageInterface|ProducerMessageInterface[] $message 消息
     * @param bool $confirm 消息是否需要确认
     * @param int $timeout 超时时间
     * @author Verdeint。
     */
    public static function produce(
        ProducerMessageInterface|array $message,
        bool $confirm = true,
        int $timeout = 5
    ): bool {
        /** @var AmqpProducer */
        $producer = ApplicationContext::getContainer()->get(AmqpProducer::class);
        if (is_array($message)) {
            $isOK = true;
            foreach ($message as $message2) {
                if (!$producer->produce($message2, $confirm, $timeout)) {
                    $isOK = false;
                }
            }
            return $isOK;
        }
        return $producer->produce($message, $confirm, $timeout);
    }
}

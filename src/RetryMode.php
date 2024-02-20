<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

/**
 * 重试模式
 * @author Verdient。
 */
enum RetryMode: string
{
    /**
     * 重新入队
     * @author Verdient。
     */
    case REQUEUE = 'REQUEUE';

    /**
     * 立即重试
     * @author Verdient。
     */
    case IMMEDIATE = 'IMMEDIATE';
}

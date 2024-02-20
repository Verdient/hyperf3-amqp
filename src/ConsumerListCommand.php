<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Command\Command;
use Hyperf\Contract\ContainerInterface;
use Verdient\cli\Console;

/**
 * 消费者列表
 * @author Verdient。
 */
class ConsumerListCommand extends Command
{
    use ParseConsumers;

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('consumer:list');
        $this->setDescription('展示消费者列表');
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function handle()
    {
        $consumers = $this->parseConsumers();

        if (empty($consumers)) {
            return $this->error('生产者为空');
        }

        /** @var EnablerManager */
        $enablerManager = $this->container->get(EnablerManager::class);

        $data = [];
        foreach ($consumers as $name => $producer) {
            $name = str_replace('\\', '.', $producer['class']);
            $data[] = [
                $name,
                $producer['description'],
                $producer['exchange'],
                $producer['routingKey'],
                $producer['queue'],
                $producer['enable'] ? '是' : '否',
                $enablerManager->getEnablerName($producer['class']),
                $producer['pool']
            ];
        }
        Console::table($data, [
            '名称',
            '描述',
            '交换机',
            '路由键',
            '队列',
            '启用',
            '开关名称（环境变量）',
            '连接池'
        ]);
    }
}

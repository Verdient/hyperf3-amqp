<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Command\Command;
use Verdient\cli\Console;

/**
 * 生产者列表
 * @author Verdient。
 */
class ProducerListCommand extends Command
{
    use ParseProducers;

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __construct()
    {
        parent::__construct('producer:list');
        $this->setDescription('展示生产者列表');
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function handle()
    {
        $producers = $this->parseProducers();

        if (empty($producers)) {
            return $this->error('生产者为空');
        }

        $data = [];
        foreach ($producers as $name => $producer) {
            $name = str_replace('\\', '.', $producer['class']);
            $data[] = [
                $name,
                $producer['description'],
                $producer['exchange'],
                $producer['routingKey'],
                $producer['pool']
            ];
        }
        Console::table($data, [
            '名称',
            '描述',
            '交换机',
            '路由键',
            '连接池'
        ]);
    }
}

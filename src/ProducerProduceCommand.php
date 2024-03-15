<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Amqp;

use Hyperf\Command\Command;
use Hyperf\Di\ReflectionManager;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use ReflectionNamedType;
use Symfony\Component\Console\Input\InputArgument;
use Verdient\cli\Console;

/**
 * 生产消息
 * @author Verdient。
 */
class ProducerProduceCommand extends Command
{
    use ParseProducers;

    /**
     * @inheritdoc
     * @author Verdient。
     */
    protected bool $coroutine = true;

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __construct()
    {
        parent::__construct('producer:produce');
        $this->setDescription('生产消息');
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function handle()
    {
        $producers = [];

        foreach ($this->parseProducers() as $producer) {
            $key = str_replace('\\', '.', $producer['class']);
            $producer['key'] = $key;
            $producers[$key] = $producer;
        }

        if (empty($producers)) {
            return $this->error('没有可用的生产者');
        }

        $inputName = $this->input->getArgument('name');

        $name = $inputName;

        if (empty($inputName)) {
            choice:
            $choices = [];
            $maxLength = 0;

            foreach ($producers as $key => $producer) {
                $length = strlen($key);
                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            $map = [];

            foreach ($producers as $key => $producer) {
                $description = $key . '  ' . str_repeat(' ', $maxLength - strlen($key)) . $producer['description'];
                $choices[] = $description;
                $map[$description] = $key;
            }

            $choice = $this->choice('请选择生产的消息', $choices);

            $name = $map[$choice];
        } else {
            if (!isset($producers[$name])) {
                return $this->error('生产者名称 ' . $name . ' 不匹配');
            }
        }

        $producer = $producers[$name];

        Console::stdout('生产者: ');
        Console::stdout(implode(' ', array_unique([$producer['key'], $producer['description']])), Console::BOLD, Console::FG_YELLOW);
        Console::output('');

        $reflectClass = ReflectionManager::reflectClass($producer['class']);

        $constructor = $reflectClass->getConstructor();

        $hasParameters = !empty($constructor->getParameters());

        if ($hasParameters) {
            $args = [];
            $docCommentParams = [];
            if ($docComment = $constructor->getDocComment()) {
                foreach ($this->getDocCommentParams($docComment) as $param) {
                    $docCommentParams[$param->getVariableName()] = [
                        'type' => (string) $param->getType(),
                        'description' => $param->getDescription()->getBodyTemplate()
                    ];
                }
            }

            params:
            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                if (!$type = $param->getType()) {
                    if (isset($docCommentParams[$name])) {
                        $type = $docCommentParams[$name]['type'];
                    }
                }
                $defaultValueAvailable = $param->isDefaultValueAvailable();
                $default = $defaultValueAvailable ? $param->getDefaultValue() : null;
                $args[$name] = $this->getParam($name, $type, $defaultValueAvailable, $default, $docCommentParams[$name]['description'] ?? null);
            }
            $message = $reflectClass->newInstanceArgs($args);
        } else {
            $message = $reflectClass->newInstance();
        }

        Producer::produce($message);

        $this->info('消息生产成功');

        if ($hasParameters) {
            goto params;
        } else if (!$inputName) {
            goto choice;
        }
    }

    /**
     * 获取注释定义的参数
     * @param string $docComment 注释
     * @return Param[]
     * @author Verdient。
     */
    protected function getDocCommentParams($docComment)
    {
        return DocBlockFactory::createInstance()->create($docComment)->getTagsByName('param');
    }

    /**
     * 获取参数
     * @param string $name 参数名称
     * @param ReflectionNamedType $type 参数类型
     * @param bool $hasDefault 是否有默认值
     * @param mixed $default 默认值
     * @param string $description 参数描述
     * @return mixed
     * @author Verdient。
     */
    protected function getParam($name, $type, $hasDefault, $default = null, $description = null,)
    {
        if (!$type) {
            $type = 'string';
        }
        $paramType = is_string($type) ? $type : $type->getName();
        if (in_array($paramType, ['int', 'float', 'string'])) {
            $tip = $description ? ('请输入 ' . $description . '(' . $name . ')') : ('请输入 ' . $name);
            if ($hasDefault) {
                return $this->ask($tip) ?: $default;
            }
            $input = null;
            while ($input === null) {
                $input = $this->ask($tip);
            }
            return $input;
        } else if ($paramType === 'array') {
            $tip = $description ? ('请输入 ' . $description . '(' . $name . ')' . ', 多个值请使用,隔开') : ('请输入 ' . $name . ', 多个值请使用,隔开');
            if ($hasDefault) {
                if ($input = $this->ask($tip)) {
                    return explode(',', $input);
                }
                return $default;
            }
            $input = null;
            while ($input === null) {
                $input = $this->ask($tip);
            }
            return array_map('trim', array_filter(explode(',', $input)));
        }
        return null;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, '生产者名称']
        ];
    }
}

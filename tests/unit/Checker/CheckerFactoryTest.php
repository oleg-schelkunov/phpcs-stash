<?php

namespace Phpcs\Checker;

use PhpCsStash\Checker\CheckerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Prophecy\Prophecy\ObjectProphecy;

class CheckerFactoryTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider provide_get_with_invalid_config
     */
    public function test_get_throws_exception_when_invalid_set_of_options_provided($type, $options)
    {
        /**
         * @var $loggerProphecy LoggerInterface|ObjectProphecy
         */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        CheckerFactory::get($type, $options, $loggerProphecy->reveal());
    }

    /**
     * @expectedException \Exception
     */
    public function test_get_throws_exception_when_invalid_type_of_checker_provided()
    {
        /**
         * @var $loggerProphecy LoggerInterface|ObjectProphecy
         */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        CheckerFactory::get('invalid_type', [], $loggerProphecy->reveal());
    }

    /**
     * @dataProvider provide_get_with_valid_config
     */
    public function test_get_creates_valid_instance_of_checker($type, $options)
    {
        /**
         * @var $loggerProphecy LoggerInterface|ObjectProphecy
         */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        CheckerFactory::get($type, $options, $loggerProphecy->reveal());
    }

    public function provide_get_with_valid_config()
    {
        return [
            'phpcs' => ['phpcs', ['standard' => 'PSR2', 'encoding' => 'utf-8', 'installed_paths' => '']],
            'cpp' => ['cpp', ['cpplint' => '', 'tmpdir' => '', 'python27Executable' => '', 'lineLength' => 10]],
        ];
    }

    public function provide_get_with_invalid_config()
    {
        return [
            'empty' => ['cpp', []],
            'invalid options with valid' => ['cpp', ['standard' => 'PSR2', 'invalid_options' => true]],
            'not all valid options' => ['cpp', ['standard' => 'PSR2']],
        ];
    }
}

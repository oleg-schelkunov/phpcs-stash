<?php

namespace PhpCsStash\Checker;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \PhpCsStash\Checker\PhpCs
 */
class PhpCsTest extends TestCase
{
    /**
     * @covers ::processFile
     */
    public function test_processFile_returns_empty_array_when_given_empty_content()
    {
        $phpCs = $this->createPhpCs();
        $result = $phpCs->processFile('test', 'php', '');
        static::assertSame([], $result);
    }

    /**
     * @covers ::processFile
     */
    public function test_processFile_returns_array_with_error_when_provided_with_invalid_content()
    {
        $phpCs = $this->createPhpCs();
        $result = $phpCs->processFile('test', 'php', '<?php echo 123; ?>');
        static::assertInternalType('array', $result);
        static::assertNotEmpty($result);
    }

    /**
     * @covers ::shouldIgnoreFile
     */
    public function test_shouldIgnoreFile_returns_false_when_provided_with_incorrect_params()
    {
        $phpCs = $this->createPhpCs();
        $result = $phpCs->shouldIgnoreFile('1', '2', '3');
        static::assertFalse($result);
    }

    private function createPhpCs()
    {
        /**
         * @var $loggerProphecy LoggerInterface|ObjectProphecy
         */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->debug(Argument::any())->willReturn(true);

        return new PhpCs($loggerProphecy->reveal(), new CheckerOptions('PSR2', 'utf-8', ''));
    }
}

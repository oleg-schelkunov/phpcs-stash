<?php

namespace PhpCsStash\Checker;

use PhpCsStash\Exception\Runtime;
use Psr\Log\LoggerInterface;

class CheckerFactory
{
    const TYPE_PHPCS = 'phpcs';

    const TYPE_CPP = 'cpp';

    /**
     * @param $type
     * @param array $config
     * @param LoggerInterface $logger
     * @return CheckerInterface
     */
    public static function get($type, array $config, LoggerInterface $logger)
    {
        switch ($type) {

            case self::TYPE_PHPCS:
                self::assertConfig($config, ['standard', 'encoding', 'installed_paths']);

                $options = new CheckerOptions(
                    $config['standard'],
                    $config['encoding'],
                    $config['installed_paths']
                );

                return new PhpCs($logger, $options);

            case self::TYPE_CPP:
                self::assertConfig($config, ['cpplint', 'tmpdir', 'python27Executable', 'lineLength']);
                return new Cpp($logger, $config);

            default:
                throw new Runtime(sprintf('Unknown checker type "%s"', $type));
        }
    }

    private static function assertConfig(array $config, $requiredOptions)
    {
        if (array_diff_key(array_flip($requiredOptions), $config)) {
            throw new \InvalidArgumentException(
                sprintf("Invalid configuration provided, required options are: %s", implode(', ', $requiredOptions))
            );
        }
    }
}

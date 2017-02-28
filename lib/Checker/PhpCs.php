<?php
/**
 * @author Evgeny Sisoev
 *
 * Интерфейсы для проверки файлов разными спрособами
 */
namespace PhpCsStash\Checker;

use Psr\Log\LoggerInterface;

class PhpCs implements CheckerInterface
{
     /**
     * @var \PHP_CodeSniffer
     */
    private $phpcs;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * PhpCs constructor.
     * @param LoggerInterface $log
     * @param CheckerOptions $options
     */
    public function __construct(LoggerInterface $log, CheckerOptions $options)
    {
        $this->log = $log;

        if ($options->getInstalledPaths()) {
            $this->log->debug(sprintf('installed_paths: %s', $options->getInstalledPaths()));
        }

        $this->phpcs = new \PHP_CodeSniffer(
            $verbosity = 0,
            $tabWidth = 0,
            $options->getEncoding(),
            $interactive = false
        );

        $this->log->debug("PhpCs config" . print_r($options, true));

        $this->phpcs->cli->setCommandLineValues([
            '--report=json',
            sprintf('--standard=%s', $options->getStandard()),
            sprintf('--runtime-set installed_paths %s', $options->getInstalledPaths()),
        ]);

        $this->phpcs->initStandard($options->getStandard());
    }

    /**
     * @param string $filename
     * @param string $extension
     * @param string $dir
     * @return bool
     */
    public function shouldIgnoreFile($filename, $extension, $dir)
    {
        return $this->phpcs->shouldIgnoreFile($filename, "./");
    }

    /**
     * @param string $filename
     * @param string $extension
     * @param string $fileContent
     * @return array
     */
    public function processFile($filename, $extension, $fileContent)
    {
        $phpCsResult = $this->phpcs->processFile($filename, $fileContent);

        return $phpCsResult->getErrors();
    }
}

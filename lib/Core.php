<?php
/**
 * @author Artem Naumenko
 *
 * Ядро проекта, подгружает конфигурацию, создает объект логирования
 */
namespace PhpCsStash;

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use PhpCsStash\Api\ApiUser;
use PhpCsStash\Api\BranchConfig;
use PhpCsStash\Checker\CheckerOptions;

class Core
{
    /** @var StashApi */
    protected $stash;

    /** @var Logger */
    protected $log;

    /** @var array */
    protected $config;

    /**
     * Core constructor.
     * @param string $configFilename путь к ini файлу конфигурации
     */
    public function __construct($configFilename)
    {
        $this->config = parse_ini_file($configFilename, true);

        $this->initLogger();

        $stashConfig = $this->getConfigSection('stash');

        $user = new ApiUser($stashConfig['username'], $stashConfig['password']);

        $config = [
            'base_url' => sprintf("%s/rest/api/1.0/", rtrim($stashConfig['url'], '/')),
            'defaults' => [
                'timeout' => $stashConfig['httpTimeout'],
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'allow_redirects' => true,
                'auth' => [$stashConfig['username'], $stashConfig['password']],
            ],
        ];

        $client = new Client($config);

        $this->stash = new StashApi(
            $this->getLogger(),
            $client,
            $user
        );
    }

    protected function initLogger()
    {
        $this->log = new Logger(uniqid());
        $dir = $this->config['logging']['dir']."/";

        $this->log->pushHandler(
            new StreamHandler($dir.date("Y-m-d").".log", $this->config['logging']['verbosityLog'])
        );

        $this->log->pushHandler(
            new StreamHandler($dir.date("Y-m-d").".log", $this->config['logging']['verbosityError'])
        );

        $this->log->pushHandler(
            new BrowserConsoleHandler()
        );
    }

    /** @return Logger */
    public function getLogger()
    {
        return $this->log;
    }

    /**
     * @return StashApi
     */
    public function getStash()
    {
        return $this->stash;
    }

    /**
     * @param string $section название секции в ini файле конфигурации
     * @return array
     */
    public function getConfigSection($section)
    {
        return $this->config[$section];
    }

    /**
     * Runs application synchronously
     *
     * @param BranchConfig $config
     * @return array
     */
    public function runSync(BranchConfig $config)
    {
        if (empty($config->getBranch()) || empty($config->getRepo()) || empty($config->getSlug())) {
            $this->getLogger()->warning("Invalid request: empty slug or branch or repo", $_GET);
            throw new \InvalidArgumentException("Invalid request: empty slug or branch or repo");
        }

        $requestProcessor = $this->createRequestProcessor();

        return $requestProcessor->processRequest($config);
    }

    /**
     * @return RequestProcessor
     * @throws Exception\Runtime
     */
    protected function createRequestProcessor()
    {
        $requestProcessor = new RequestProcessor(
            $this->getLogger(),
            $this->getStash(),
            $this->createChecker()
        );

        return $requestProcessor;
    }

    /**
     * @return Checker\CheckerInterface
     * @throws Exception\Runtime
     */
    protected function createChecker()
    {
        $type = $this->getConfigSection('core')['type'];

        if ($type == 'phpcs') {
            $options = $this->getConfigSection('phpcs');
            return new Checker\PhpCs($this->log, new CheckerOptions(
                $options['standard'],
                $options['encoding'],
                $options['installed_paths']
            ));
        } elseif ($type == 'cpp') {
            return new Checker\Cpp($this->log, $this->getConfigSection('cpp'));
        } else {
            throw new Exception\Runtime("Unknown checker type");
        }
    }
}

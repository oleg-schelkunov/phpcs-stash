<?php
/**
 * @author Artem Naumenko
 *
 * Ядро проекта, подгружает конфигурацию, создает объект логирования
 */
namespace PhpCsStash;

use PhpCsStash\Checker\CheckerInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use PhpCsStash\Api\ApiUser;
use PhpCsStash\Api\BranchConfig;
use PhpCsStash\Checker\CheckerOptions;

class Core
{
    /**
     * @var StashApi
     */
    protected $stash;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var CheckerInterface
     */
    private $checker;

    /**
     * @param array $stashConfig
     * @param LoggerInterface $logger
     * @param CheckerInterface $checker
     */
    public function __construct(array $stashConfig, LoggerInterface $logger, CheckerInterface $checker)
    {
        $this->log = $logger;
        $this->checker = $checker;

        $user = new ApiUser($stashConfig['username'], $stashConfig['password']);

        $config = [
            'base_url' => sprintf("%s/rest/api/1.0/", rtrim($stashConfig['url'], '/')),
            'defaults' => [
                'timeout' => $stashConfig['timeout'],
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'allow_redirects' => true,
                'auth' => [$stashConfig['username'], $stashConfig['password']],
            ],
        ];

        $client = new Client($config);

        $this->stash = new StashApi(
            $this->log,
            $client,
            $user
        );
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
            $this->log->warning("Invalid request: empty slug or branch or repo", $_GET);
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
            $this->log,
            $this->stash,
            $this->checker
        );

        return $requestProcessor;
    }
}

<?php
/**
 * @author Artem Naumenko
 *
 * Класс для интеграции с atlassian stash. Реализует базовую функциональность API
 * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html
 */
namespace PhpCsStash;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PhpCsStash\Api\ApiUser;
use PhpCsStash\Api\BranchConfig;
use PhpCsStash\Exception\StashFileInConflict;
use Psr\Log\LoggerInterface;

/**
 * Class StashApi
 * @package PhpCsStash
 */
class StashApi
{
    const HTTP_TIMEOUT = 90;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $username;

    /**
     * @param LoggerInterface $logger
     * @param ClientInterface $client
     * @param ApiUser $user
     */
    public function __construct(LoggerInterface $logger, ClientInterface $client, ApiUser $user)
    {
        $this->username = $user->getUsername();
        $this->logger = $logger;

        $this->httpClient = $client;
    }

    /**
     * Возвращает имя текущего пользователя
     * @return string
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * Возвращает содержимое файла в данной ветке
     *
     * @param string $slug
     * @param string $repo
     * @param string $ref
     * @param string $filename
     * @return string
     */
    public function getFileContent($slug, $repo, $pullRequestId, $filename)
    {
        $changes = $this->getPullRequestDiffs($slug, $repo, $pullRequestId, 100000, $filename);

        $result = [];
        foreach ($changes['diffs'] as $diff) {
            if ($diff['destination']['toString'] !== $filename) {
                continue;
            }

            foreach ($diff['hunks'] as $hunk) {
                foreach ($hunk['segments'] as $segment) {
                    foreach ($segment['lines'] as $line) {
                        if (!empty($line['conflictMarker'])) {
                            throw new StashFileInConflict("File $filename is in conflict state");
                        }

                        $result[$line['destination']] = $line['line'];
                    }
                }
            }
        }

        ksort($result);

        return implode("\n", $result)."\n";
    }

    /**
     * Возвращает содержимое файла в данной ветке
     *
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param int    $contextLines
     * @param string $path
     * @return array
     *
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp992528
     */
    public function getPullRequestDiffs($slug, $repo, $pullRequestId, $contextLines = 10, $path = "")
    {
        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/diff/$path", "GET", [
            'contextLines' => $contextLines,
            'withComments' => 'false',
        ]);
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param string $filename
     *
     * @return array
     *
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp36368
     */
    public function getPullRequestComments($slug, $repo, $pullRequestId, $filename)
    {
        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/comments", "GET", [
            'path' => $filename,
            'limit' => 1000,
        ]);
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param string $filename
     *
     * @return array
     *
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp137840
     */
    public function getPullRequestActivities($slug, $repo, $pullRequestId)
    {
        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/activities", "GET", [
            'limit' => 1000,
        ]);
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     */
    public function addMeToPullRequestReviewers($slug, $repo, $pullRequestId)
    {
        $user = [
            "name" => $this->username,
        ];

        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/participants", "POST", [
            'user' => $user,
            'role' => "REVIEWER",
        ]);
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     */
    public function approvePullRequest($slug, $repo, $pullRequestId)
    {
        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/approve", "POST",
            []
        );
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     */
    public function unapprovePullRequest($slug, $repo, $pullRequestId)
    {
        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/approve", "DELETE",
            []
        );
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param string $filename
     * @param int    $line
     * @param string $text
     * @return array
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp895840
     */
    public function addPullRequestComment($slug, $repo, $pullRequestId, $filename, $line, $text)
    {
        $anchor = [
            "line" => $line,
            "lineType" => "ADDED",
            "fileType" => "TO",
            'path' => $filename,
            'srcPath' => $filename,
        ];

        return $this->sendRequest("projects/$slug/repos/$repo/pull-requests/$pullRequestId/comments", "POST", [
            'text' => $text,
            'anchor' => $anchor,
        ]);
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param int    $commentId
     * @param int    $version
     * @return array
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp895840
     */
    public function deletePullRequestComment($slug, $repo, $pullRequestId, $version, $commentId)
    {
        return $this->sendRequest(
            "projects/$slug/repos/$repo/pull-requests/$pullRequestId/comments/$commentId/?version=$version",
            "DELETE",
            []
        );
    }

    /**
     * @param string $slug
     * @param string $repo
     * @param int    $pullRequestId
     * @param int    $commentId
     * @param int    $version
     * @param string $text
     * @return array
     *
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp1467264
     */
    public function updatePullRequestComment($slug, $repo, $pullRequestId, $commentId, $version, $text)
    {
        $request = [
            'version' => $version,
            'text' => $text,
        ];

        return $this->sendRequest(
            "projects/$slug/repos/$repo/pull-requests/$pullRequestId/comments/$commentId",
            "PUT",
            $request
        );
    }

    /**
     * Returns all pull request for a branch
     *
     * We assume that one branch would not have more than 100 pull requests :)
     *
     * @see https://developer.atlassian.com/static/rest/stash/3.11.3/stash-rest.html#idp992528
     *
     * @param BranchConfig $config
     * @return array
     */
    public function getPullRequestsByBranch(BranchConfig $config)
    {
        $query = [
            "state" => "open",
            "at" => $config->getBranch(),
            "direction" => "OUTGOING",
            "limit" => 100,
        ];

        return $this->sendRequest($this->getBaseUrl($config), "GET", $query);
    }

    private function getBaseUrl(BranchConfig $config)
    {
        return sprintf('projects/%s/repos/%s/pull-requests', $config->getSlug(), $config->getRepo());
    }

    private function sendRequest($url, $method, $request)
    {
        try {
            if (strtoupper($method) == 'GET') {
                $this->logger->debug("Sending GET request to $url, query=" . json_encode($request));
                $response = $this->httpClient->get($url, ['query' => $request]);
            } else {
                $this->logger->debug("Sending $method request to $url, body=" . json_encode($request));
                $response = $this->httpClient->send(
                    $this->httpClient->createRequest($method, $url, ['body' => json_encode($request)])
                );
            }
        } catch (RequestException $e) {
            // Stash error: it can't send more then 1mb of json data. So just skip suck pull requests or files
            $this->logger->debug("Request finished with error: " . $e->getMessage());
            if ($e->getMessage() == 'cURL error 56: Problem (3) in the Chunked-Encoded data') {
                throw new Exception\StashJsonFailure($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
            } else {
                throw $e;
            }
        }

        $this->logger->debug("Request finished");

        $json = (string) $response->getBody();

        //an: пустой ответ - значит все хорошо
        if (empty($json)) {
            return true;
        }

        $data = json_decode($json, true);

        if ($data === null && $data != 'null') {
            $this->logger->addError("Invalid json received", [
                'url' => $url,
                'method' => $method,
                'request' => $request,
                'reply' => $json,
            ]);

            throw new \Exception('invalid_json_received');
        }

        return $data;
    }
}

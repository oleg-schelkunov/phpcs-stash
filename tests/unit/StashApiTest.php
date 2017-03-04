<?php

namespace PhpCsStash;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use PhpCsStash\Api\ApiUser;
use PhpCsStash\Api\BranchConfig;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @covers \PhpCsStash\StashApi
 */
class StashApiTest extends TestCase
{
    public function test_getPullRequestsByBranch_returns_valid_result()
    {
        $api = $this->createStashApi();
        $result = $api->getPullRequestsByBranch($this->createBranchConfig());

        static::assertInternalType('array', $result);
    }

    public function createBranchConfig()
    {
        return new BranchConfig('branch', 'slug', 'repo');
    }

    public function createStashApi()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->debug(Argument::any())->willReturn(true);

        $requestProphecy = $this->prophesize(RequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn('{}');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->createRequest(Argument::any())->willReturn($requestProphecy->reveal());
        $clientProphecy->get(Argument::any(), Argument::any())->willReturn($responseProphecy->reveal());
        $clientProphecy->send(Argument::any())->willReturn($responseProphecy->reveal());

        $user = new ApiUser('name', 'pwd');

        return new StashApi($loggerProphecy->reveal(), $clientProphecy->reveal(), $user);
    }
}

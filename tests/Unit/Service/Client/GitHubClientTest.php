<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Service\Client;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Service\Client\GitHubClient;

final class GitHubClientTest extends TestCase
{
    public function testGetLoginUrlContainsClientIdAndScope(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $client = new GitHubClient(['clientId' => 'my-client-id', 'clientSecret' => 'secret'], $httpClient);
        $url = $client->getLoginUrl(null, new RedirectBehaviour());

        self::assertStringStartsWith('https://github.com/login/oauth/authorize?', $url);
        self::assertStringContainsString('client_id=my-client-id', $url);
        self::assertStringContainsString('scope=user%3Aemail', $url);
    }

    public function testGetLoginUrlIncludesStateWhenProvided(): void
    {
        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $this->createStub(HttpClientInterface::class));
        $url = $client->getLoginUrl('my-state', new RedirectBehaviour(stateKey: 'state'));

        self::assertStringContainsString('state=my-state', $url);
    }

    public function testGetLoginUrlOmitsStateWhenNull(): void
    {
        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $this->createStub(HttpClientInterface::class));
        $url = $client->getLoginUrl(null, new RedirectBehaviour());

        self::assertStringNotContainsString('state=', $url);
    }

    public function testGetLoginUrlIncludesRedirectUriWhenSet(): void
    {
        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $this->createStub(HttpClientInterface::class));
        $url = $client->getLoginUrl(null, new RedirectBehaviour(redirectUri: 'https://example.com/callback'));

        self::assertStringContainsString('redirect_uri=', $url);
    }

    public function testGetLoginUrlOmitsRedirectUriWhenNull(): void
    {
        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $this->createStub(HttpClientInterface::class));
        $url = $client->getLoginUrl(null, new RedirectBehaviour(redirectUri: null));

        self::assertStringNotContainsString('redirect_uri=', $url);
    }

    public function testGetUserReturnsUserFromProfileEmail(): void
    {
        $tokenResponse = $this->mockResponse(['access_token' => 'tok123']);
        $userResponse = $this->mockResponse([
            'id' => 42,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userResponse);

        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $httpClient);
        $user = $client->getUser('state', 'code123', new RedirectBehaviour());

        self::assertSame('42', $user->primaryKey);
        self::assertSame('jane@example.com', $user->primaryEmail);
        self::assertSame(['jane@example.com'], $user->emails);
        self::assertSame('Jane', $user->firstName);
        self::assertSame('Doe', $user->lastName);
    }

    public function testGetUserFetchesEmailsEndpointWhenProfileEmailIsEmpty(): void
    {
        $tokenResponse = $this->mockResponse(['access_token' => 'tok123']);
        $userResponse = $this->mockResponse(['id' => 7, 'name' => 'Ghost', 'email' => null]);
        $emailsResponse = $this->mockResponse([
            ['email' => 'other@example.com', 'primary' => false, 'verified' => true],
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userResponse, $emailsResponse);

        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $httpClient);
        $user = $client->getUser('state', 'code', new RedirectBehaviour());

        self::assertSame('primary@example.com', $user->primaryEmail);
    }

    public function testGetUserThrowsWhenNoVerifiedPrimaryEmail(): void
    {
        $tokenResponse = $this->mockResponse(['access_token' => 'tok123']);
        $userResponse = $this->mockResponse(['id' => 7, 'name' => 'Ghost', 'email' => null]);
        $emailsResponse = $this->mockResponse([
            ['email' => 'unverified@example.com', 'primary' => true, 'verified' => false],
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userResponse, $emailsResponse);

        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $httpClient);

        $this->expectException(\RuntimeException::class);

        $client->getUser('state', 'code', new RedirectBehaviour());
    }

    public function testGetUserThrowsWhenAccessTokenMissing(): void
    {
        $tokenResponse = $this->mockResponse(['error' => 'bad_verification_code']);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($tokenResponse);

        $client = new GitHubClient(['clientId' => 'id', 'clientSecret' => 'secret'], $httpClient);

        $this->expectException(\RuntimeException::class);

        $client->getUser('state', 'bad-code', new RedirectBehaviour());
    }

    private function mockResponse(array $data): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);
        return $response;
    }
}
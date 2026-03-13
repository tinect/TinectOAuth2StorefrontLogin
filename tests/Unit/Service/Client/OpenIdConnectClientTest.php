<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Service\Client;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Service\Client\OpenIdConnectClient;

final class OpenIdConnectClientTest extends TestCase
{
    private function makeClient(array $configOverrides = []): OpenIdConnectClient
    {
        $config = array_merge([
            'clientId' => 'my-client',
            'clientSecret' => 'my-secret',
            'authorization_endpoint' => 'https://provider.example.com/auth',
            'token_endpoint' => 'https://provider.example.com/token',
            'userinfo_endpoint' => 'https://provider.example.com/userinfo',
            'scopes' => ['openid', 'email', 'profile'],
        ], $configOverrides);

        return new OpenIdConnectClient($config, $this->createStub(HttpClientInterface::class));
    }

    public function testGetLoginUrlContainsRequiredParams(): void
    {
        $client = $this->makeClient();
        $url = $client->getLoginUrl(null, new RedirectBehaviour());

        self::assertStringStartsWith('https://provider.example.com/auth?', $url);
        self::assertStringContainsString('client_id=my-client', $url);
        self::assertStringContainsString('response_type=code', $url);
        self::assertStringContainsString('scope=openid+email+profile', $url);
    }

    public function testGetLoginUrlIncludesStateWhenProvided(): void
    {
        $client = $this->makeClient();
        $url = $client->getLoginUrl('my-state', new RedirectBehaviour(stateKey: 'state'));

        self::assertStringContainsString('state=my-state', $url);
    }

    public function testGetLoginUrlOmitsStateWhenNull(): void
    {
        $client = $this->makeClient();
        $url = $client->getLoginUrl(null, new RedirectBehaviour());

        self::assertStringNotContainsString('state=', $url);
    }

    public function testGetLoginUrlIncludesRedirectUriWhenSet(): void
    {
        $client = $this->makeClient();
        $url = $client->getLoginUrl(null, new RedirectBehaviour(redirectUri: 'https://shop.example.com/callback'));

        self::assertStringContainsString('redirect_uri=', $url);
    }

    public function testGetUserReturnsUserFromUserinfoResponse(): void
    {
        $config = [
            'clientId' => 'id',
            'clientSecret' => 'secret',
            'authorization_endpoint' => 'https://provider.example.com/auth',
            'token_endpoint' => 'https://provider.example.com/token',
            'userinfo_endpoint' => 'https://provider.example.com/userinfo',
            'scopes' => ['openid', 'email'],
        ];

        $tokenResponse = $this->mockResponse(['access_token' => 'tok456']);
        $userInfoResponse = $this->mockResponse([
            'sub' => 'user-sub-123',
            'email' => 'john@example.com',
            'given_name' => 'John',
            'family_name' => 'Smith',
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $client = new OpenIdConnectClient($config, $httpClient);
        $user = $client->getUser('state', 'code', new RedirectBehaviour());

        self::assertSame('user-sub-123', $user->primaryKey);
        self::assertSame('john@example.com', $user->primaryEmail);
        self::assertSame(['john@example.com'], $user->emails);
        self::assertSame('John', $user->firstName);
        self::assertSame('Smith', $user->lastName);
    }

    public function testGetUserFallsBackToNameParsingWhenGivenFamilyNameMissing(): void
    {
        $config = [
            'clientId' => 'id',
            'clientSecret' => 'secret',
            'authorization_endpoint' => 'https://p.example.com/auth',
            'token_endpoint' => 'https://p.example.com/token',
            'userinfo_endpoint' => 'https://p.example.com/userinfo',
            'scopes' => ['openid', 'email'],
        ];

        $tokenResponse = $this->mockResponse(['access_token' => 'tok']);
        $userInfoResponse = $this->mockResponse([
            'sub' => 'sub-abc',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $client = new OpenIdConnectClient($config, $httpClient);
        $user = $client->getUser('state', 'code', new RedirectBehaviour());

        self::assertSame('Jane', $user->firstName);
        self::assertSame('Doe', $user->lastName);
    }

    public function testGetUserThrowsWhenAccessTokenMissing(): void
    {
        $config = [
            'clientId' => 'id',
            'clientSecret' => 'secret',
            'authorization_endpoint' => 'https://p.example.com/auth',
            'token_endpoint' => 'https://p.example.com/token',
            'userinfo_endpoint' => 'https://p.example.com/userinfo',
            'scopes' => ['openid'],
        ];

        $tokenResponse = $this->mockResponse(['error' => 'invalid_grant']);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($tokenResponse);

        $client = new OpenIdConnectClient($config, $httpClient);

        $this->expectException(\RuntimeException::class);

        $client->getUser('state', 'bad-code', new RedirectBehaviour());
    }

    public function testGetUserThrowsWhenEmailMissingFromUserinfo(): void
    {
        $config = [
            'clientId' => 'id',
            'clientSecret' => 'secret',
            'authorization_endpoint' => 'https://p.example.com/auth',
            'token_endpoint' => 'https://p.example.com/token',
            'userinfo_endpoint' => 'https://p.example.com/userinfo',
            'scopes' => ['openid'],
        ];

        $tokenResponse = $this->mockResponse(['access_token' => 'tok']);
        $userInfoResponse = $this->mockResponse(['sub' => 'sub-abc']); // no email

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $client = new OpenIdConnectClient($config, $httpClient);

        $this->expectException(\RuntimeException::class);

        $client->getUser('state', 'code', new RedirectBehaviour());
    }

    private function mockResponse(array $data): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);
        return $response;
    }
}
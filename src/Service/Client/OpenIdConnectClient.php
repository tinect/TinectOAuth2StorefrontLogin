<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Contract\User;

final class OpenIdConnectClient extends ClientContract
{
    public function __construct(
        private readonly array $config,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getLoginUrl(?string $state, RedirectBehaviour $behaviour): string
    {
        $scopes = $this->config['scopes'];
        $scopeString = implode(' ', $scopes);

        $params = [
            'client_id' => $this->config['clientId'],
            'response_type' => 'code',
            'scope' => $scopeString,
        ];

        if ($behaviour->redirectUri !== null) {
            $params['redirect_uri'] = $behaviour->redirectUri;
        }

        if ($state !== null && $state !== '') {
            $params[$behaviour->stateKey] = $state;
        }

        return $this->config['authorization_endpoint'] . '?' . http_build_query($params);
    }

    public function getUser(string $state, string $code, RedirectBehaviour $behaviour): User
    {
        $tokenParams = [
            'grant_type' => 'authorization_code',
            $behaviour->codeKey => $code,
            'client_id' => $this->config['clientId'],
            'client_secret' => $this->config['clientSecret'],
        ];

        if ($behaviour->redirectUri !== null) {
            $tokenParams['redirect_uri'] = $behaviour->redirectUri;
        }

        $tokenResponse = $this->httpClient->request('POST', $this->config['token_endpoint'], [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query($tokenParams),
        ]);

        $tokenData = $tokenResponse->toArray();

        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException('OIDC: failed to obtain access token');
        }

        $accessToken = $tokenData['access_token'];

        $userInfoResponse = $this->httpClient->request('GET', $this->config['userinfo_endpoint'], [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        $userInfo = $userInfoResponse->toArray();
        $email = $userInfo['email'] ?? null;

        if (empty($email)) {
            throw new \RuntimeException('OIDC: could not retrieve an email address from the provider. Ensure the "email" scope is requested and the provider returns email claims.');
        }

        $sub = $userInfo['sub'];
        $fullName = trim($userInfo['name'] ?? '');
        $nameParts = explode(' ', $fullName, 2);

        $user = new User();
        $user->primaryKey = (string) $sub;
        $user->primaryEmail = $email;
        $user->emails = [$email];
        $user->firstName = $userInfo['given_name'] ?? ($nameParts[0] ?: 'OIDC');
        $user->lastName = $userInfo['family_name'] ?? ($nameParts[1] ?? 'User');

        return $user;
    }
}

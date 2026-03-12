<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Contract\User;

final class MicrosoftEntraClient extends ClientContract
{
    public function __construct(
        private readonly array $config,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getLoginUrl(?string $state, RedirectBehaviour $behaviour): string
    {
        $params = [
            'client_id' => $this->config['clientId'],
            'response_type' => 'code',
            'scope' => implode(' ', $this->config['scopes']),
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

        $tokenData = $this->httpClient->request('POST', $this->config['token_endpoint'], [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query($tokenParams),
        ])->toArray();

        $accessToken = $tokenData['access_token'] ?? null;

        if ($accessToken === null) {
            throw new \RuntimeException('Microsoft Entra: failed to obtain access token');
        }

        $userInfo = $this->httpClient->request('GET', $this->config['userinfo_endpoint'], [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ])->toArray();

        $emails = [$userInfo['email'] ?? null];

        $emails = array_filter($emails, static fn ($email) => filter_var($email, \FILTER_VALIDATE_EMAIL));

        if (empty($emails)) {
            throw new \RuntimeException('Microsoft Entra: could not retrieve an email address. Ensure the "email" scope is granted and the app exposes email claims.');
        }

        $fullName = trim($userInfo['name'] ?? '');
        $nameParts = explode(' ', $fullName, 2);

        $user = new User();
        $user->primaryKey = (string) $userInfo['sub'];
        $user->primaryEmail = $emails[0];
        $user->emails = $emails;
        $user->firstName = $userInfo['given_name'] ?? ($nameParts[0] ?: 'Entra');
        $user->lastName = $userInfo['family_name'] ?? ($nameParts[1] ?? 'User');

        return $user;
    }
}

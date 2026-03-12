<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Contract\User;

final class GitHubClient extends ClientContract
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
            'scope' => 'user:email',
        ];

        if ($behaviour->redirectUri !== null) {
            $params['redirect_uri'] = $behaviour->redirectUri;
        }

        if ($state !== null && $state !== '') {
            $params[$behaviour->stateKey] = $state;
        }

        return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    }

    public function getUser(string $state, string $code, RedirectBehaviour $behaviour): User
    {
        $tokenParams = [
            'client_id' => $this->config['clientId'],
            'client_secret' => $this->config['clientSecret'],
            $behaviour->codeKey => $code,
        ];

        if ($behaviour->redirectUri !== null) {
            $tokenParams['redirect_uri'] = $behaviour->redirectUri;
        }

        $tokenResponse = $this->httpClient->request('POST', 'https://github.com/login/oauth/access_token', [
            'headers' => ['Accept' => 'application/json'],
            'json' => $tokenParams,
        ]);

        $tokenData = $tokenResponse->toArray();

        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException('GitHub OAuth: failed to obtain access token');
        }

        $accessToken = $tokenData['access_token'];

        $userResponse = $this->httpClient->request('GET', 'https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Shopware-TinectOAuth-Plugin',
            ],
        ]);

        $userData = $userResponse->toArray();
        $email = $userData['email'] ?? null;

        if (empty($email)) {
            $emailsResponse = $this->httpClient->request('GET', 'https://api.github.com/user/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Shopware-TinectOAuth-Plugin',
                ],
            ]);

            foreach ($emailsResponse->toArray() as $emailEntry) {
                if (($emailEntry['primary'] ?? false) === true && ($emailEntry['verified'] ?? false) === true) {
                    $email = $emailEntry['email'];
                    break;
                }
            }
        }

        if (empty($email)) {
            throw new \RuntimeException('GitHub OAuth: could not retrieve a verified primary email address. Please make sure your GitHub account has a verified email.');
        }

        $fullName = trim($userData['name'] ?? $userData['login'] ?? '');
        $nameParts = explode(' ', $fullName, 2);

        $user = new User();
        $user->primaryKey = (string) ($userData['id'] ?? '');
        $user->primaryEmail = $email;
        $user->emails = [$email];
        $user->firstName = $nameParts[0] ?: 'GitHub';
        $user->lastName = $nameParts[1] ?? 'User';

        return $user;
    }
}

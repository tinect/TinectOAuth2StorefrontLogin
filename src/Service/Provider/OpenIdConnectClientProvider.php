<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Service\Client\OpenIdConnectClient;

final class OpenIdConnectClientProvider extends ClientProviderContract
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function provides(): string
    {
        return 'open_id_connect';
    }

    public function getConfigurationTemplate(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->define('clientId')
            ->required()
            ->allowedTypes('string');

        $resolver->define('clientSecret')
            ->required()
            ->allowedTypes('string');

        $resolver->define('discoveryDocumentUrl')
            ->required()
            ->allowedTypes('string');

        $resolver->define('authorization_endpoint')
            ->required()
            ->allowedTypes('string');

        $resolver->define('token_endpoint')
            ->required()
            ->allowedTypes('string');

        $resolver->define('userinfo_endpoint')
            ->required()
            ->allowedTypes('string');

        $resolver->define('scopes')
            ->default(['openid', 'email', 'profile'])
            ->allowedTypes('array');

        return $resolver;
    }

    public function getInitialConfiguration(): array
    {
        return [
            'clientId' => '',
            'clientSecret' => '',
            'discoveryDocumentUrl' => '',
            'authorization_endpoint' => '',
            'token_endpoint' => '',
            'userinfo_endpoint' => '',
            'scopes' => ['openid', 'email', 'profile'],
        ];
    }

    public function provideClient(array $resolvedConfig): ClientContract
    {
        if (
            $resolvedConfig['discoveryDocumentUrl'] !== ''
            && (
                $resolvedConfig['authorization_endpoint'] === ''
                || $resolvedConfig['token_endpoint'] === ''
                || $resolvedConfig['userinfo_endpoint'] === ''
            )
        ) {
            $response = $this->httpClient->request('GET', $resolvedConfig['discoveryDocumentUrl']);
            $discovery = $response->toArray();

            foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
                if (isset($discovery[$key]) && $resolvedConfig[$key] === '') {
                    $this->assertHttpsEndpoint($discovery[$key], $key);
                    $resolvedConfig[$key] = $discovery[$key];
                }
            }
        }

        return new OpenIdConnectClient($resolvedConfig, $this->httpClient);
    }
}

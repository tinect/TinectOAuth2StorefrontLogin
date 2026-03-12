<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Service\Client\OpenIdConnectClient;

final class MicrosoftEntraClientProvider extends ClientProviderContract
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function provides(): string
    {
        return 'microsoft_entra';
    }

    public function getConfigurationTemplate(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->define('tenantId')
            ->required()
            ->allowedTypes('string');

        $resolver->define('clientId')
            ->required()
            ->allowedTypes('string');

        $resolver->define('clientSecret')
            ->required()
            ->allowedTypes('string');

        return $resolver;
    }

    public function getInitialConfiguration(): array
    {
        return [
            'tenantId' => '',
            'clientId' => '',
            'clientSecret' => '',
        ];
    }

    public function provideClient(array $resolvedConfig): ClientContract
    {
        $discoveryUrl = \sprintf(
            'https://login.microsoftonline.com/%s/v2.0/.well-known/openid-configuration',
            $resolvedConfig['tenantId'],
        );

        $discovery = $this->httpClient->request('GET', $discoveryUrl)->toArray();

        foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
            $this->assertHttpsEndpoint($discovery[$key], $key);
        }

        return new OpenIdConnectClient([
            'clientId' => $resolvedConfig['clientId'],
            'clientSecret' => $resolvedConfig['clientSecret'],
            'authorization_endpoint' => $discovery['authorization_endpoint'],
            'token_endpoint' => $discovery['token_endpoint'],
            'userinfo_endpoint' => $discovery['userinfo_endpoint'],
            'scopes' => ['openid', 'email', 'profile'],
        ], $this->httpClient);
    }
}

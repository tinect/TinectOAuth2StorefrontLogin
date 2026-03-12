<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Service\Client\OpenIdConnectClient;

final class GoogleMailClientProvider extends ClientProviderContract
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function provides(): string
    {
        return 'google_mail';
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

        return $resolver;
    }

    public function getInitialConfiguration(): array
    {
        return [
            'clientId' => '',
            'clientSecret' => '',
        ];
    }

    public function provideClient(array $resolvedConfig): ClientContract
    {
        $discovery = $this->httpClient
            ->request('GET', 'https://accounts.google.com/.well-known/openid-configuration')
            ->toArray();

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

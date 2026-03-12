<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Service\Client\GitHubClient;

final class GitHubClientProvider extends ClientProviderContract
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function provides(): string
    {
        return 'github';
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
        return new GitHubClient($resolvedConfig, $this->httpClient);
    }
}

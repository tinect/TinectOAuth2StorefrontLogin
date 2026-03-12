<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Contract;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthProviderNotConfiguredException;

final readonly class ClientFactory
{
    public function __construct(
        private ClientProviderRepositoryContract $clientProviderRepository,
    ) {
    }

    public function create(string $providerKey, array $configuration): ClientContract
    {
        $clientProvider = $this->clientProviderRepository->getMatchingProvider($providerKey);

        if ($clientProvider === null) {
            throw new OAuthProviderNotConfiguredException($providerKey);
        }

        try {
            $resolvedConfig = $clientProvider->getConfigurationTemplate()->resolve($configuration);
        } catch (ExceptionInterface $e) {
            throw new OAuthProviderNotConfiguredException($providerKey, $e);
        }

        return $clientProvider->provideClient($resolvedConfig);
    }
}

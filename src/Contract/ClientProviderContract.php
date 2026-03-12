<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Contract;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class ClientProviderContract
{
    public function getConfigurationTemplate(): OptionsResolver
    {
        return new OptionsResolver();
    }

    public function getInitialConfiguration(): array
    {
        return [];
    }

    abstract public function provides(): string;

    abstract public function provideClient(array $resolvedConfig): ClientContract;

    protected function assertHttpsEndpoint(string $url, string $field): void
    {
        if (!str_starts_with($url, 'https://')) {
            throw new \RuntimeException(\sprintf(
                'Discovery document field "%s" must use HTTPS, got: "%s"',
                $field,
                $url,
            ));
        }
    }
}

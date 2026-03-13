<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientFactory;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderRepositoryContract;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Contract\User;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthProviderNotConfiguredException;

final class ClientFactoryTest extends TestCase
{
    public function testCreateThrowsWhenProviderNotFound(): void
    {
        $repo = new ClientProviderRepositoryContract([]);
        $factory = new ClientFactory($repo);

        $this->expectException(OAuthProviderNotConfiguredException::class);

        $factory->create('unknown', []);
    }

    public function testCreateThrowsWhenConfigurationIsInvalid(): void
    {
        $provider = new class() extends ClientProviderContract {
            public function provides(): string { return 'test'; }

            public function getConfigurationTemplate(): OptionsResolver
            {
                $resolver = new OptionsResolver();
                $resolver->setRequired('clientId');
                return $resolver;
            }

            public function provideClient(array $resolvedConfig): ClientContract
            {
                throw new \LogicException('Should not be called');
            }
        };

        $repo = new ClientProviderRepositoryContract([$provider]);
        $factory = new ClientFactory($repo);

        $this->expectException(OAuthProviderNotConfiguredException::class);

        $factory->create('test', []); // missing required 'clientId'
    }

    public function testCreateReturnsClientOnValidConfig(): void
    {
        $expectedClient = new class() extends ClientContract {
            public function getLoginUrl(?string $state, RedirectBehaviour $behaviour): string { return ''; }
            public function getUser(string $state, string $code, RedirectBehaviour $behaviour): User { return new User(); }
        };

        $provider = new class($expectedClient) extends ClientProviderContract {
            public function __construct(private readonly ClientContract $client) {}

            public function provides(): string { return 'test'; }

            public function getConfigurationTemplate(): OptionsResolver
            {
                $resolver = new OptionsResolver();
                $resolver->setDefined('clientId');
                return $resolver;
            }

            public function provideClient(array $resolvedConfig): ClientContract
            {
                return $this->client;
            }
        };

        $repo = new ClientProviderRepositoryContract([$provider]);
        $factory = new ClientFactory($repo);

        $client = $factory->create('test', ['clientId' => 'abc']);

        self::assertSame($expectedClient, $client);
    }
}
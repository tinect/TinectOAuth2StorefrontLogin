<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderRepositoryContract;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Contract\User;

final class ClientProviderRepositoryContractTest extends TestCase
{
    private function makeProvider(string $key): ClientProviderContract
    {
        return new class($key) extends ClientProviderContract {
            public function __construct(private readonly string $key) {}

            public function provides(): string
            {
                return $this->key;
            }

            public function provideClient(array $resolvedConfig): ClientContract
            {
                return new class() extends ClientContract {
                    public function getLoginUrl(?string $state, RedirectBehaviour $behaviour): string { return ''; }
                    public function getUser(string $state, string $code, RedirectBehaviour $behaviour): User { return new User(); }
                };
            }
        };
    }

    public function testGetMatchingProviderReturnsCorrectProvider(): void
    {
        $provider = $this->makeProvider('github');
        $repo = new ClientProviderRepositoryContract([$provider]);

        self::assertSame($provider, $repo->getMatchingProvider('github'));
    }

    public function testGetMatchingProviderReturnsNullForUnknownKey(): void
    {
        $repo = new ClientProviderRepositoryContract([]);

        self::assertNull($repo->getMatchingProvider('unknown'));
    }

    public function testGetMatchingProviderIgnoresOtherProviders(): void
    {
        $repo = new ClientProviderRepositoryContract([
            $this->makeProvider('github'),
            $this->makeProvider('google_mail'),
        ]);

        self::assertNull($repo->getMatchingProvider('microsoft_entra'));
    }

    public function testGetProviders(): void
    {
        $p1 = $this->makeProvider('github');
        $p2 = $this->makeProvider('google_mail');
        $repo = new ClientProviderRepositoryContract([$p1, $p2]);

        self::assertSame([$p1, $p2], $repo->getProviders());
    }

    public function testGetProviderKeysReturnsUniqueKeys(): void
    {
        $repo = new ClientProviderRepositoryContract([
            $this->makeProvider('github'),
            $this->makeProvider('github'),
            $this->makeProvider('google_mail'),
        ]);

        self::assertSame(['github', 'google_mail'], $repo->getProviderKeys());
    }

    public function testGetProviderKeysReturnsEmptyArrayWhenNoProviders(): void
    {
        $repo = new ClientProviderRepositoryContract([]);

        self::assertSame([], $repo->getProviderKeys());
    }
}
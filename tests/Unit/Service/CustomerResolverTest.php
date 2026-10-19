<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tinect\OAuth2StorefrontLogin\Contract\User;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerConnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerDisconnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerEmailUpdatedEvent;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthAccountAlreadyConnectedException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailMismatchException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEnforcedProviderException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthNoAccountFoundException;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;
use Tinect\OAuth2StorefrontLogin\Service\EnforcedEmailDomainService;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\DataProvider;

final class CustomerResolverTest extends TestCase
{
    private string $clientId;
    private string $customerId;

    protected function setUp(): void
    {
        $this->clientId = Uuid::randomHex();
        $this->customerId = Uuid::randomHex();
    }

    // -------------------------------------------------------------------------
    // connect()
    // -------------------------------------------------------------------------

    public function testConnectThrowsEmailMismatchWhenTrustEmailAndEmailsDontMatch(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['provider@example.com'];

        $customer = $this->createStub(CustomerEntity::class);
        $customer->method('getEmail')->willReturn('shop@example.com');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);

        $resolver = $this->makeResolver();

        $this->expectException(OAuthEmailMismatchException::class);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: true);
    }

    public function testConnectThrowsEmailMismatchWhenTrustEmailAndCustomerHasNoEmail(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['provider@example.com'];

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn(null);

        $resolver = $this->makeResolver();

        $this->expectException(OAuthEmailMismatchException::class);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: true);
    }

    public function testConnectSucceedsWhenTrustEmailAndEmailsMatch(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['John@Example.com']; // mixed case to test normalisation

        $customer = $this->createStub(CustomerEntity::class);
        $customer->method('getEmail')->willReturn('john@example.com');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false); // no existing key binding
        $connection->expects(self::once())->method('executeStatement'); // storeCustomerKey

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(OAuthCustomerConnectedEvent::class));

        $resolver = $this->makeResolver(connection: $connection, eventDispatcher: $eventDispatcher);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: true);
    }

    public function testConnectThrowsAlreadyConnectedWhenKeyBoundToDifferentCustomer(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';

        $otherCustomerId = Uuid::randomHex();

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn($otherCustomerId);

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection);

        $this->expectException(OAuthAccountAlreadyConnectedException::class);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: false);
    }

    public function testConnectSucceedsWhenKeyAlreadyBoundToSameCustomer(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($this->customerId);
        $connection->expects(self::once())->method('executeStatement'); // INSERT IGNORE (no-op)

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(OAuthCustomerConnectedEvent::class));

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, eventDispatcher: $eventDispatcher);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: false);
    }

    public function testConnectSkipsKeyStorageWhenPrimaryKeyIsEmpty(): void
    {
        $user = new User();
        $user->primaryKey = '';

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);
        $connection->expects(self::never())->method('executeStatement');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, eventDispatcher: $eventDispatcher);

        $resolver->connect($user, $this->clientId, $this->customerId, $context, trustEmail: false);
    }

    // -------------------------------------------------------------------------
    // disconnect()
    // -------------------------------------------------------------------------

    public function testDisconnectExecutesDeleteAndDispatchesEvent(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(self::stringContains('DELETE FROM tinect_oauth_storefront_customer_key'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(OAuthCustomerDisconnectedEvent::class));

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, eventDispatcher: $eventDispatcher);

        $resolver->disconnect($this->clientId, $this->customerId, $context);
    }

    // -------------------------------------------------------------------------
    // resolve()
    // -------------------------------------------------------------------------

    public function testResolveThrowsNoAccountFoundWhenRegistrationDisabledAndNoKeyOrEmailMatch(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn(false); // no key match, no email match

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection);

        $this->expectException(OAuthNoAccountFoundException::class);

        $resolver->resolve($user, $this->clientId, 'any', $context, allowRegistration: false);
    }

    public function testResolveLoginsDirectlyByExistingOAuthKey(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn($existingCustomerId);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())
            ->method('loginById')
            ->with($existingCustomerId);

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService);

        $resolver->resolve($user, $this->clientId, 'any', $context);
    }

    public function testResolveLinksAndLoginsExistingCustomerByEmail(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            false,              // findCustomerIdByKey → no key match
            $existingCustomerId // findCustomerIdByEmail → email match
        );
        $connection->expects(self::once())->method('executeStatement'); // storeCustomerKey

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())
            ->method('loginById')
            ->with($existingCustomerId);

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService);

        $resolver->resolve($user, $this->clientId, 'any', $context);
    }

    public function testResolveTrustEmailLoginsWhenKeyAndEmailBothMatch(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($existingCustomerId); // findCustomerIdByKeyAndEmail

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())
            ->method('loginById')
            ->with($existingCustomerId);

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService);

        $resolver->resolve($user, $this->clientId, 'any', $context, trustEmail: true);
    }

    public function testResolveTrustEmailThrowsMismatchWhenKeyFoundButEmailDiffers(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            false,               // findCustomerIdByKeyAndEmail → no combined match
            $existingCustomerId  // findCustomerIdByKey → key exists (different email)
        );

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection);

        $this->expectException(OAuthEmailMismatchException::class);

        $resolver->resolve($user, $this->clientId, 'any', $context, trustEmail: true);
    }

    public function testResolveTrustEmailFallsThroughToEmailLookupWhenNoKeyFound(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['user@example.com'];

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            false,              // findCustomerIdByKeyAndEmail → no match
            false,              // findCustomerIdByKey → no match
            $existingCustomerId // findCustomerIdByEmail → email match
        );
        $connection->expects(self::once())->method('executeStatement'); // storeCustomerKey

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())
            ->method('loginById')
            ->with($existingCustomerId);

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService);

        $resolver->resolve($user, $this->clientId, 'any', $context, trustEmail: true);
    }

    public function testResolveUpdatesEmailWhenUpdateEmailOnLoginEnabled(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->emails = ['new@example.com'];
        $user->primaryEmail = 'new@example.com';

        $existingCustomerId = Uuid::randomHex();

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            $existingCustomerId, // findCustomerIdByKey
            'old@example.com',   // updateCustomerEmail → fetch old email
        );
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(self::stringContains('UPDATE customer SET email'));

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())
            ->method('loginById')
            ->with($existingCustomerId);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(OAuthCustomerEmailUpdatedEvent::class));

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(
            connection: $connection,
            accountService: $accountService,
            eventDispatcher: $eventDispatcher,
        );

        $resolver->resolve($user, $this->clientId, 'any', $context, updateEmailOnLogin: true);
    }

    public function testEmailLinkingPolicyRejectsExistingAccountsBeforeWritingOrLogin(): void
    {
        $user = new User();
        $user->primaryKey = 'new-key';
        $user->emails = ['customer@example.com'];
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(false, $this->customerId);
        $connection->expects(self::never())->method('executeStatement');
        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('loginById');
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event): object {
            if ($event instanceof \Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerResolveEvent) {
                $event->allowEmailLinking = false;
            }
            return $event;
        });
        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());
        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService, eventDispatcher: $dispatcher);
        $this->expectException(\Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailAlreadyRegisteredException::class);
        $resolver->resolve($user, $this->clientId, 'Google', $context);
    }

    public static function salesChannelScenarios(): iterable
    {
        yield 'shared account, key' => [false, false, false, false, true];
        yield 'shared account, key and email' => [false, true, false, false, true];
        yield 'shared account, email' => [false, false, true, false, true];
        yield 'foreign bound account, key' => [true, false, false, false, false];
        yield 'foreign bound account, email' => [true, false, true, false, false];
        yield 'same subject in another OAuth client' => [false, false, false, false, false, true];
        yield 'bound account in its own channel' => [true, false, false, false, true, false, true];
        yield 'shared account, changed email' => [false, true, false, true, false];
    }

    #[DataProvider('salesChannelScenarios')]
    public function testRespectsBindingAcrossCreationChannels(bool $bound, bool $trustEmail, bool $emailOnly, bool $changedEmail, bool $login, bool $otherClient = false, bool $sameChannel = false): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE customer (id BLOB, email TEXT, sales_channel_id BLOB, bound_sales_channel_id BLOB, active INTEGER, guest INTEGER)');
        $connection->executeStatement('CREATE TABLE tinect_oauth_storefront_customer_key (customer_id BLOB, client_id BLOB, primary_key TEXT)');
        $customerId = $this->customerId;
        $clientId = $this->clientId;
        $creationChannelId = Uuid::randomHex();
        $connection->insert('customer', [
            'id' => Uuid::fromHexToBytes($customerId),
            'email' => 'customer@example.com',
            'sales_channel_id' => Uuid::fromHexToBytes($creationChannelId),
            'bound_sales_channel_id' => $bound ? Uuid::fromHexToBytes($creationChannelId) : null,
            'active' => 1,
            'guest' => 0,
        ]);
        $connection->insert('tinect_oauth_storefront_customer_key', [
            'customer_id' => Uuid::fromHexToBytes($customerId),
            'client_id' => Uuid::fromHexToBytes($clientId),
            'primary_key' => 'subject',
        ]);
        $user = new User();
        $user->primaryKey = $emailOnly ? '' : 'subject';
        $user->emails = $otherClient ? [] : [$changedEmail ? 'changed@example.com' : 'customer@example.com'];
        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn($sameChannel ? $creationChannelId : Uuid::randomHex());
        $accountService = $this->createMock(AccountService::class);
        $accountService->expects($login ? self::once() : self::never())->method('loginById')->with($customerId);
        $resolver = $this->makeResolver(connection: $connection, accountService: $accountService);

        if (!$login) {
            $this->expectException($changedEmail ? OAuthEmailMismatchException::class : OAuthNoAccountFoundException::class);
        }
        // An empty subject exercises email lookup without writing a new key.
        $resolver->resolve($user, $otherClient ? Uuid::randomHex() : $clientId, 'Google', $context, allowRegistration: false, trustEmail: $trustEmail);
    }

    // -------------------------------------------------------------------------
    // enforced email domains
    // -------------------------------------------------------------------------

    public function testResolveRejectsOtherClientForEnforcedProviderEmail(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->primaryEmail = 'max@example.com';
        $user->emails = ['max@example.com'];

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('loginById');

        $resolver = $this->makeResolver(
            accountService: $accountService,
            enforcedEmailDomains: $this->makeEnforcedEmailDomains(Uuid::randomHex()),
        );

        $this->expectException(OAuthEnforcedProviderException::class);

        $resolver->resolve($user, $this->clientId, 'GitHub', $this->createStub(SalesChannelContext::class));
    }

    public function testResolveRejectsOtherClientWhenKeyMappedCustomerHasEnforcedEmail(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->primaryEmail = 'max@private.org';
        $user->emails = ['max@private.org'];

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            $this->customerId, // findActiveCustomerIdByKey
            'max@example.com', // customer email
        );

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('loginById');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(
            connection: $connection,
            accountService: $accountService,
            enforcedEmailDomains: $this->makeEnforcedEmailDomains(Uuid::randomHex()),
        );

        $this->expectException(OAuthEnforcedProviderException::class);

        $resolver->resolve($user, $this->clientId, 'GitHub', $context);
    }

    public function testResolveRandomizesPasswordWhenLoggingInViaEnforcedClient(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->primaryEmail = 'max@example.com';
        $user->emails = ['max@example.com'];

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            $this->customerId, // findActiveCustomerIdByKey
            'max@example.com', // customer email
        );

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())->method('loginById')->with($this->customerId);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->expects(self::once())
            ->method('update')
            ->with(self::callback(fn (array $data) => $data[0]['id'] === $this->customerId && \is_string($data[0]['password'])));

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(
            connection: $connection,
            accountService: $accountService,
            enforcedEmailDomains: $this->makeEnforcedEmailDomains($this->clientId),
            customerRepository: $customerRepository,
        );

        $resolver->resolve($user, $this->clientId, 'Entra', $context);
    }

    public function testResolveKeepsPasswordWhenDomainIsNotEnforced(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->primaryEmail = 'max@private.org';
        $user->emails = ['max@private.org'];

        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            $this->customerId, // findActiveCustomerIdByKey
            'max@private.org', // customer email
        );

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->expects(self::never())->method('update');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn(Uuid::randomHex());

        $resolver = $this->makeResolver(
            connection: $connection,
            enforcedEmailDomains: $this->makeEnforcedEmailDomains($this->clientId),
            customerRepository: $customerRepository,
        );

        $resolver->resolve($user, $this->clientId, 'Entra', $context);
    }

    public function testConnectRejectsOtherClientForEnforcedCustomerEmail(): void
    {
        $user = new User();
        $user->primaryKey = 'key-123';
        $user->primaryEmail = 'max@private.org';
        $user->emails = ['max@private.org'];

        $customer = $this->createStub(CustomerEntity::class);
        $customer->method('getEmail')->willReturn('max@example.com');

        $context = $this->createStub(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeStatement');

        $resolver = $this->makeResolver(
            connection: $connection,
            enforcedEmailDomains: $this->makeEnforcedEmailDomains(Uuid::randomHex()),
        );

        $this->expectException(OAuthEnforcedProviderException::class);

        $resolver->connect($user, $this->clientId, $this->customerId, $context);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEnforcedEmailDomains(string $clientId): EnforcedEmailDomainService
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['id' => $clientId, 'enforced_email_domains' => '["example.com"]'],
        ]);

        return new EnforcedEmailDomainService($connection);
    }

    private function makeResolver(
        ?Connection $connection = null,
        ?AccountService $accountService = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?EnforcedEmailDomainService $enforcedEmailDomains = null,
        ?EntityRepository $customerRepository = null,
    ): CustomerResolver {
        return new CustomerResolver(
            $connection ?? $this->createStub(Connection::class),
            $accountService ?? $this->createStub(AccountService::class),
            $this->createStub(AbstractRegisterRoute::class),
            $this->createStub(RequestStack::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $customerRepository ?? $this->createStub(EntityRepository::class),
            $enforcedEmailDomains ?? new EnforcedEmailDomainService($this->createStub(Connection::class)),
        );
    }
}
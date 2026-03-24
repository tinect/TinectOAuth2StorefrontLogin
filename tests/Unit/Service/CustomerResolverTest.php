<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tinect\OAuth2StorefrontLogin\Contract\User;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerConnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerDisconnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerEmailUpdatedEvent;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthAccountAlreadyConnectedException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailMismatchException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthNoAccountFoundException;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;

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

        $resolver = $this->makeResolver(
            connection: $connection,
            accountService: $accountService,
            eventDispatcher: $eventDispatcher,
        );

        $resolver->resolve($user, $this->clientId, 'any', $context, updateEmailOnLogin: true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeResolver(
        ?Connection $connection = null,
        ?AccountService $accountService = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): CustomerResolver {
        return new CustomerResolver(
            $connection ?? $this->createStub(Connection::class),
            $accountService ?? $this->createStub(AccountService::class),
            $this->createStub(AbstractRegisterRoute::class),
            $this->createStub(RequestStack::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }
}
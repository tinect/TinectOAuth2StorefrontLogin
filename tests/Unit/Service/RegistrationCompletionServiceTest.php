<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailConfirmationRequiredException;
use Tinect\OAuth2StorefrontLogin\Service\RegistrationCompletionService;
use Tinect\OAuth2StorefrontLogin\Subscriber\OAuthRegistrationSubscriber;

final class RegistrationCompletionServiceTest extends TestCase
{
    public static function doubleOptInSettings(): iterable
    {
        yield 'without double opt-in' => [false];
        yield 'with double opt-in' => [true];
    }

    #[DataProvider('doubleOptInSettings')]
    public function testCompletionDispatchesOnlyOnceAndPreservesOtherCustomFields(bool $doubleOptIn): void
    {
        $customer = $this->customer($doubleOptIn);
        if ($doubleOptIn) {
            $customer->setDoubleOptInConfirmDate(new \DateTimeImmutable());
        }
        $repository = $this->repository($customer);
        $repository->expects(self::once())->method('update');
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new OAuthRegistrationSubscriber($repository));
        $registrations = [];
        $dispatcher->addListener(CustomerRegisterEvent::class, static function (CustomerRegisterEvent $event) use (&$registrations): void {
            $registrations[] = $event->getCustomer();
        });
        $context = $this->createStub(SalesChannelContext::class);
        // Simulates the core event during initial registration or later email confirmation.
        $dispatcher->dispatch(new CustomerRegisterEvent($context, $customer));
        self::assertSame([], $registrations);
        $service = new RegistrationCompletionService($this->connection(), $repository, $dispatcher);
        self::assertTrue($service->complete($customer->getId(), $context));
        self::assertFalse($service->complete($customer->getId(), $context));
        self::assertCount(1, $registrations);
        self::assertFalse(RegistrationCompletionService::isPending($registrations[0]));
        self::assertTrue($customer->getCustomFields()['existing_field']);
    }

    public function testCompletionCannotBypassEmailConfirmation(): void
    {
        $customer = $this->customer(true);
        $repository = $this->repository($customer);
        $repository->expects(self::never())->method('update');
        $dispatcher = new EventDispatcher();
        $service = new RegistrationCompletionService($this->connection(), $repository, $dispatcher);
        $this->expectException(OAuthEmailConfirmationRequiredException::class);
        $service->complete($customer->getId(), $this->createStub(SalesChannelContext::class));
    }

    private function customer(bool $doubleOptIn): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setDoubleOptInRegistration($doubleOptIn);
        $customer->setCustomFields([RegistrationCompletionService::PENDING_CUSTOM_FIELD => true, 'existing_field' => true]);

        return $customer;
    }

    private function repository(CustomerEntity $customer): EntityRepository
    {
        $result = $this->createStub(EntitySearchResult::class);
        $result->method('getEntities')->willReturn(new CustomerCollection([$customer]));
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn($result);

        return $repository;
    }

    private function connection(): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (\Closure $operation): mixed => $operation());

        return $connection;
    }
}

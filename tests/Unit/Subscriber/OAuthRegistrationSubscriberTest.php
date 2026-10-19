<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Subscriber;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;
use Tinect\OAuth2StorefrontLogin\Subscriber\OAuthRegistrationSubscriber;

final class OAuthRegistrationSubscriberTest extends TestCase
{
    public static function confirmationStates(): iterable
    {
        yield 'verified OAuth registration' => [true, true, true];
        yield 'unverified OAuth registration' => [true, false, false];
        yield 'ordinary registration' => [false, false, false];
        yield 'unrelated verified state' => [false, true, false];
    }

    #[DataProvider('confirmationStates')]
    public function testOnlyVerifiedOAuthRegistrationSkipsConfirmationMail(bool $oauth, bool $verified, bool $confirm): void
    {
        $context = Context::createDefaultContext();
        if ($oauth) {
            $context->addState(CustomerResolver::STATE);
        }
        if ($verified) {
            $context->addState(CustomerResolver::STATE_VERIFIED_EMAIL);
        }
        $salesChannelContext = $this->createStub(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn($context);
        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($confirm ? self::once() : self::never())->method('update');
        $subscriber = new OAuthRegistrationSubscriber($repository);
        $event = new CustomerDoubleOptInRegistrationEvent($customer, $salesChannelContext, 'https://shop.example.com/confirm');
        $subscriber->confirmVerifiedEmail($event);
        self::assertSame($confirm, $event->isPropagationStopped());
        self::assertSame($confirm, $customer->getDoubleOptInConfirmDate() !== null);
    }
    public function testPendingFlagIsMappedBeforeCustomerCreationWithoutDiscardingCustomFields(): void
    {
        $context = Context::createDefaultContext();
        $context->addState(CustomerResolver::STATE, CustomerResolver::STATE_DEFER_COMPLETION);
        $data = new \Shopware\Core\Framework\Validation\DataBag\RequestDataBag(['customFields' => ['business_field' => 'value']]);
        $event = new \Shopware\Core\Framework\Event\DataMappingEvent($data, ['email' => 'customer@example.com'], $context);
        $subscriber = new OAuthRegistrationSubscriber($this->createStub(EntityRepository::class));
        $subscriber->markPendingRegistration($event);
        self::assertTrue($event->getOutput()['customFields'][\Tinect\OAuth2StorefrontLogin\Service\RegistrationCompletionService::PENDING_CUSTOM_FIELD]);
        self::assertSame('value', $event->getOutput()['customFields']['business_field']);
        self::assertFalse($data->has('customFields'));
    }

}

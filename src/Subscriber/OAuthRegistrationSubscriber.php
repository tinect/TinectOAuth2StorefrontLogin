<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;

final readonly class OAuthRegistrationSubscriber implements EventSubscriberInterface
{
    /** @param EntityRepository<CustomerCollection> $customerRepository */
    public function __construct(private EntityRepository $customerRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [CustomerDoubleOptInRegistrationEvent::class => ['confirmVerifiedEmail', 1000]];
    }

    public function confirmVerifiedEmail(CustomerDoubleOptInRegistrationEvent $event): void
    {
        if (!$event->getContext()->hasState(CustomerResolver::STATE)
            || !$event->getContext()->hasState(CustomerResolver::STATE_VERIFIED_EMAIL)
        ) {
            return;
        }

        $confirmedAt = new \DateTimeImmutable();
        $this->customerRepository->update([[
            'id' => $event->getCustomerId(),
            'doubleOptInConfirmDate' => $confirmedAt,
        ]], $event->getContext());
        $event->getCustomer()->setDoubleOptInConfirmDate($confirmedAt);
        // Stop the originating event so queued/delayed mail flows cannot send it later.
        $event->stopPropagation();
    }
}

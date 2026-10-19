<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Tinect\OAuth2StorefrontLogin\Service\RegistrationCompletionService;
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
        return [
            CustomerDoubleOptInRegistrationEvent::class => ['confirmVerifiedEmail', 1000],
            CustomerEvents::MAPPING_REGISTER_CUSTOMER => ['markPendingRegistration', -1000],
            CustomerRegisterEvent::class => ['deferRegistrationEvent', 1000],
        ];
    }

    public function markPendingRegistration(DataMappingEvent $event): void
    {
        if (!$event->getContext()->hasState(CustomerResolver::STATE)
            || !$event->getContext()->hasState(CustomerResolver::STATE_DEFER_COMPLETION)
        ) {
            return;
        }

        $output = $event->getOutput();
        // Data is built server-side. Preserve its custom fields while keeping the
        // internal pending flag out of the public Store API custom-field mapper.
        $output['customFields'] = array_replace(
            $output['customFields'] ?? [],
            $event->getInput()->all('customFields'),
            [RegistrationCompletionService::PENDING_CUSTOM_FIELD => true],
        );
        $event->getInput()->remove('customFields');
        $event->setOutput($output);
    }

    public function deferRegistrationEvent(CustomerRegisterEvent $event): void
    {
        // Also applies when email confirmation happens in a later request.
        if (RegistrationCompletionService::isPending($event->getCustomer())) {
            $event->stopPropagation();
        }
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

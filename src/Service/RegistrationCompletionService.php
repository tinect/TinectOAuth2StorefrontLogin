<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailConfirmationRequiredException;

final readonly class RegistrationCompletionService
{
    public const PENDING_CUSTOM_FIELD = 'tinect_oauth_registration_pending';

    /** @param EntityRepository<CustomerCollection> $customerRepository */
    public function __construct(
        private Connection $connection,
        private EntityRepository $customerRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function isPending(CustomerEntity $customer): bool
    {
        return !empty($customer->getCustomFields()[self::PENDING_CUSTOM_FIELD]);
    }

    /** Call after validating and saving the additional profile data. Returns false if already complete. */
    public function complete(string $customerId, SalesChannelContext $context): bool
    {
        $customer = $this->connection->transactional(function () use ($customerId, $context): ?CustomerEntity {
            // Serialize competing submissions before checking the durable pending flag.
            $this->connection->fetchOne('SELECT id FROM customer WHERE id = :id FOR UPDATE', ['id' => Uuid::fromHexToBytes($customerId)]);
            $criteria = (new Criteria([$customerId]))->addAssociations(['addresses', 'salutation', 'defaultBillingAddress.country']);
            $customer = $this->customerRepository->search($criteria, $context->getContext())->getEntities()->first();
            if (!$customer instanceof CustomerEntity || !self::isPending($customer)) {
                return null;
            }

            if ($customer->getDoubleOptInRegistration() && $customer->getDoubleOptInConfirmDate() === null) {
                throw new OAuthEmailConfirmationRequiredException();
            }

            $this->customerRepository->update([[
                'id' => $customerId,
                'customFields' => [self::PENDING_CUSTOM_FIELD => false],
            ]], $context->getContext());
            $customFields = $customer->getCustomFields() ?? [];
            $customFields[self::PENDING_CUSTOM_FIELD] = false;
            $customer->setCustomFields($customFields);

            return $customer;
        });

        if ($customer === null) {
            return false;
        }

        $this->eventDispatcher->dispatch(new CustomerRegisterEvent($context, $customer));

        return true;
    }
}

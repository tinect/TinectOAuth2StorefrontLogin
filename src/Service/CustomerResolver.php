<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tinect\OAuth2StorefrontLogin\Contract\User;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerConnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerDisconnectedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerEmailUpdateConflictEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerEmailUpdatedEvent;
use Tinect\OAuth2StorefrontLogin\Event\OAuthCustomerRegisteredEvent;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthAccountAlreadyConnectedException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthEmailMismatchException;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthNoAccountFoundException;

final readonly class CustomerResolver
{
    public const STATE = 'oauth2_storefront_login_resolving_customer';

    public function __construct(
        private Connection $connection,
        private AccountService $accountService,
        private AbstractRegisterRoute $registerRoute,
        private RequestStack $requestStack,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function resolve(User $user, string $clientId, SalesChannelContext $context, bool $allowRegistration = true, bool $trustEmail = false, bool $updateEmailOnLogin = false): void
    {
        $context->addState(self::STATE);

        // 1. Existing OAuth key mapping → direct login
        //    When trustEmail is enabled, the email from the provider must also
        //    match the customer the key is mapped to.
        $customerId = null;
        if ($trustEmail) {
            $customerId = $this->findCustomerIdByKeyAndEmail($clientId, $user->primaryKey, $user->emails, $context->getSalesChannelId());
        }

        if ($customerId === null) {
            $customerId = $this->findCustomerIdByKey($clientId, $user->primaryKey);

            if ($customerId !== null && $trustEmail) {
                // If we found a customer just by key but trustEmail is enabled, we need to throw mismatch exception
                throw new OAuthEmailMismatchException();
            }
        }

        if ($customerId !== null) {
            if ($updateEmailOnLogin && $user->primaryEmail !== '') {
                $this->updateCustomerEmail($customerId, $clientId, $user->primaryEmail, $context);
            }

            $this->accountService->loginById($customerId, $context);

            return;
        }

        // 2. Existing customer with matching email → link and login
        $existingCustomerId = $this->findCustomerIdByEmail($user->emails, $context->getSalesChannelId());

        if ($existingCustomerId !== null) {
            $this->storeCustomerKey($existingCustomerId, $clientId, $user->primaryKey);
            $this->accountService->loginById($existingCustomerId, $context);

            return;
        }

        // 3. No customer yet → register only if allowed
        if (!$allowRegistration) {
            throw new OAuthNoAccountFoundException();
        }

        $newCustomerId = $this->registerNewCustomer($user, $context);
        $this->storeCustomerKey($newCustomerId, $clientId, $user->primaryKey);
        $this->accountService->loginById($newCustomerId, $context);

        $this->eventDispatcher->dispatch(new OAuthCustomerRegisteredEvent($newCustomerId, $clientId, $user, $context));
    }

    public function connect(User $user, string $clientId, string $customerId, SalesChannelContext $context, bool $trustEmail = false): void
    {
        if ($trustEmail) {
            $customerEmail = strtolower(trim($context->getCustomer()?->getEmail() ?? ''));
            $providerEmails = array_map(static fn (string $e) => strtolower(trim($e)), $user->emails);

            if ($customerEmail === '' || !\in_array($customerEmail, $providerEmails, true)) {
                throw new OAuthEmailMismatchException();
            }
        }

        // Check if the OAuth key is already bound to a different customer
        $existingCustomerId = $this->findCustomerIdByKey($clientId, $user->primaryKey);

        if ($existingCustomerId !== null && $existingCustomerId !== $customerId) {
            throw new OAuthAccountAlreadyConnectedException();
        }

        $this->storeCustomerKey($customerId, $clientId, $user->primaryKey);

        $this->eventDispatcher->dispatch(new OAuthCustomerConnectedEvent($customerId, $clientId, $user, $context));
    }

    public function disconnect(string $clientId, string $customerId, SalesChannelContext $context): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tinect_oauth_storefront_customer_key
             WHERE client_id = :clientId AND customer_id = :customerId',
            [
                'clientId' => Uuid::fromHexToBytes($clientId),
                'customerId' => Uuid::fromHexToBytes($customerId),
            ],
        );

        $this->eventDispatcher->dispatch(new OAuthCustomerDisconnectedEvent($customerId, $clientId, $context));
    }

    /**
     * @param string[] $emails
     */
    private function findCustomerIdByKeyAndEmail(string $clientId, string $primaryKey, array $emails, string $salesChannelId): ?string
    {
        if ($primaryKey === '') {
            return null;
        }

        $emails = array_values(array_unique(array_map(
            static fn (string $e) => strtolower(trim($e)),
            $emails,
        )));

        if (empty($emails)) {
            return null;
        }

        $result = $this->connection->fetchOne(
            'SELECT LOWER(HEX(c.id))
             FROM customer c
             INNER JOIN tinect_oauth_storefront_customer_key k ON k.customer_id = c.id
             WHERE k.client_id = :clientId
               AND k.primary_key = :primaryKey
               AND LOWER(c.email) IN (:emails)
               AND (c.sales_channel_id = :salesChannelId OR c.sales_channel_id IS NULL)
               AND c.guest = 0',
            [
                'clientId' => Uuid::fromHexToBytes($clientId),
                'primaryKey' => $primaryKey,
                'emails' => $emails,
                'salesChannelId' => Uuid::fromHexToBytes($salesChannelId),
            ],
            [
                'emails' => ArrayParameterType::STRING,
            ],
        );

        return $result ?: null;
    }

    private function findCustomerIdByKey(string $clientId, string $primaryKey): ?string
    {
        if ($primaryKey === '') {
            return null;
        }

        $result = $this->connection->fetchOne(
            'SELECT LOWER(HEX(customer_id))
             FROM tinect_oauth_storefront_customer_key
             WHERE client_id = :clientId
               AND primary_key = :primaryKey',
            [
                'clientId' => Uuid::fromHexToBytes($clientId),
                'primaryKey' => $primaryKey,
            ],
        );

        return $result ?: null;
    }

    /**
     * @param string[] $emails
     */
    private function findCustomerIdByEmail(array $emails, string $salesChannelId): ?string
    {
        $emails = array_values(array_unique(array_map(
            static fn (string $e) => strtolower(trim($e)),
            $emails,
        )));

        if (empty($emails)) {
            return null;
        }

        $result = $this->connection->fetchOne(
            'SELECT LOWER(HEX(id))
             FROM customer
             WHERE LOWER(email) IN (:emails)
               AND (sales_channel_id = :salesChannelId OR sales_channel_id IS NULL)
               AND guest = 0
               ORDER BY sales_channel_id DESC',
            [
                'emails' => $emails,
                'salesChannelId' => Uuid::fromHexToBytes($salesChannelId),
            ],
            [
                'emails' => ArrayParameterType::STRING,
            ],
        );

        return $result ?: null;
    }

    private function updateCustomerEmail(string $customerId, string $clientId, string $newEmail, SalesChannelContext $context): void
    {
        $oldEmail = $this->connection->fetchOne(
            'SELECT email FROM customer WHERE id = :customerId',
            ['customerId' => Uuid::fromHexToBytes($customerId)],
        );

        if ($oldEmail === false || $oldEmail === $newEmail) {
            return;
        }

        try {
            $this->connection->executeStatement(
                'UPDATE customer SET email = :email, updated_at = :now WHERE id = :customerId',
                [
                    'customerId' => Uuid::fromHexToBytes($customerId),
                    'email' => $newEmail,
                    'now' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ],
            );
        } catch (UniqueConstraintViolationException $e) {
            $this->eventDispatcher->dispatch(
                new OAuthCustomerEmailUpdateConflictEvent($customerId, $clientId, $oldEmail, $newEmail, $context, $e)
            );

            return;
        }

        $this->eventDispatcher->dispatch(
            new OAuthCustomerEmailUpdatedEvent($customerId, $clientId, $oldEmail, $newEmail, $context)
        );
    }

    private function storeCustomerKey(string $customerId, string $clientId, string $primaryKey): void
    {
        if ($primaryKey === '') {
            return;
        }

        $this->connection->executeStatement(
            'INSERT IGNORE INTO tinect_oauth_storefront_customer_key
                (id, customer_id, client_id, primary_key, created_at)
             VALUES
                (:id, :customerId, :clientId, :primaryKey, :now)',
            [
                'id' => Uuid::randomBytes(),
                'customerId' => Uuid::fromHexToBytes($customerId),
                'clientId' => Uuid::fromHexToBytes($clientId),
                'primaryKey' => $primaryKey,
                'now' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        );
    }

    private function registerNewCustomer(User $user, SalesChannelContext $context): string
    {
        $firstName = $user->firstName ?: 'OAuth';
        $lastName = $user->lastName ?: 'User';

        $billingAddress = new DataBag([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'street' => '-',
            'zipcode' => '-',
            'city' => '-',
            'countryId' => $context->getSalesChannel()->getCountryId(),
        ]);

        $storefrontUrl = $this->requestStack->getCurrentRequest()?->attributes->get(RequestTransformer::STOREFRONT_URL) ?? '';

        $data = new RequestDataBag([
            'email' => $user->primaryEmail,
            'password' => Random::getString(32),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'guest' => false,
            'billingAddress' => $billingAddress,
            'acceptedDataProtection' => true,
            'storefrontUrl' => $storefrontUrl,
        ]);

        $response = $this->registerRoute->register($data, $context, false);

        return $response->getCustomer()->getId();
    }
}

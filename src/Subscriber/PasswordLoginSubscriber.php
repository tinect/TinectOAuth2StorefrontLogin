<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;

final readonly class PasswordLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerBeforeLoginEvent::class => 'onBeforeLogin',
        ];
    }

    public function onBeforeLogin(CustomerBeforeLoginEvent $event): void
    {
        if ($event->getContext()->hasState(CustomerResolver::STATE)) {
            return;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $result = $this->connection->fetchOne(
            'SELECT 1
             FROM customer c
             INNER JOIN tinect_oauth_storefront_customer_key k ON k.customer_id = c.id
             INNER JOIN tinect_oauth_storefront_client cl ON cl.id = k.client_id
             WHERE LOWER(c.email) = :email
               AND (c.sales_channel_id = :salesChannelId OR c.sales_channel_id IS NULL)
               AND c.guest = 0
               AND cl.disable_password_login = 1
               AND cl.active = 1
             LIMIT 1',
            [
                'email' => strtolower(trim($event->getEmail())),
                'salesChannelId' => Uuid::fromHexToBytes($salesChannelId),
            ],
        );

        if ($result !== false) {
            throw CustomerException::badCredentials();
        }
    }
}

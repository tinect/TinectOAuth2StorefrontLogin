<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthPasswordLoginDisabledException;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;

final readonly class PasswordLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerBeforeLoginEvent::class => 'onBeforeLogin',
            KernelEvents::EXCEPTION => ['onKernelException', 100],
        ];
    }

    public function onBeforeLogin(CustomerBeforeLoginEvent $event): void
    {
        if ($event->getContext()->hasState(CustomerResolver::STATE)) {
            return;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $result = $this->connection->fetchOne(
            'SELECT LOWER(HEX(cl.id))
             FROM customer c
             INNER JOIN tinect_oauth_storefront_customer_key k ON k.customer_id = c.id
             INNER JOIN tinect_oauth_storefront_client cl ON cl.id = k.client_id
             WHERE LOWER(c.email) = :email
               AND (c.sales_channel_id = :salesChannelId OR c.sales_channel_id IS NULL)
               AND c.guest = 0
               AND cl.force_o_auth = 1
               AND cl.active = 1
             LIMIT 1',
            [
                'email' => strtolower(trim($event->getEmail())),
                'salesChannelId' => Uuid::fromHexToBytes($salesChannelId),
            ],
        );

        if ($result !== false) {
            throw new OAuthPasswordLoginDisabledException((string) $result);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof OAuthPasswordLoginDisabledException) {
            return;
        }

        $params = $throwable->clientId !== '' ? ['clientId' => $throwable->clientId] : [];

        $route = $throwable->clientId !== ''
            ? 'widgets.tinect.oauth.redirect'
            : 'frontend.account.login.page';

        $event->setResponse(new RedirectResponse(
            $this->router->generate($route, $params),
        ));

        $event->stopPropagation();
    }
}

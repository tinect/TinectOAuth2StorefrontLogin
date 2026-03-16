<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthPasswordLoginDisabledException;

final readonly class PasswordLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
        private TranslatorInterface $translator,
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
            throw new OAuthPasswordLoginDisabledException();
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof OAuthPasswordLoginDisabledException) {
            return;
        }

        $request = $event->getRequest();

        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add(
                'danger',
                $this->translator->trans('tinect-oauth.error.passwordLoginDisabled'),
            );
        }

        $event->setResponse(new RedirectResponse(
            $this->router->generate('frontend.account.login.page'),
        ));

        $event->stopPropagation();
    }
}

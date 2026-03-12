<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Event;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class OAuthCustomerDisconnectedEvent
{
    public function __construct(
        public string $customerId,
        public string $clientId,
        public SalesChannelContext $salesChannelContext,
    ) {
    }
}

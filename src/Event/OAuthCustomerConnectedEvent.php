<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Event;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Tinect\OAuth2StorefrontLogin\Contract\User;

readonly class OAuthCustomerConnectedEvent
{
    public function __construct(
        public string $customerId,
        public string $clientId,
        public User $user,
        public SalesChannelContext $salesChannelContext,
    ) {
    }
}

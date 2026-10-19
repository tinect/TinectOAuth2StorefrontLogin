<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Event;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Tinect\OAuth2StorefrontLogin\Contract\User;

/** Fired after linked-account lookup, before linking by email or registering. */
final class OAuthCustomerResolveEvent
{
    public bool $allowEmailLinking = true;

    public function __construct(
        public readonly User $user,
        public readonly string $clientId,
        public readonly SalesChannelContext $salesChannelContext,
    ) {
    }
}

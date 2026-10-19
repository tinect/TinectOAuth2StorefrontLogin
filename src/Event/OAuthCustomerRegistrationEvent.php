<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Event;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Tinect\OAuth2StorefrontLogin\Contract\User;

/** Allows server-side registration data changes before validation and persistence. */
final class OAuthCustomerRegistrationEvent
{
    public bool $deferCompletion = false;

    public function __construct(
        public readonly User $user,
        public readonly string $clientId,
        public readonly RequestDataBag $data,
        public readonly SalesChannelContext $salesChannelContext,
    ) {
    }
}

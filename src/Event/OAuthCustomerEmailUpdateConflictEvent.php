<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Event;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class OAuthCustomerEmailUpdateConflictEvent
{
    public function __construct(
        public string $customerId,
        public string $clientId,
        public string $currentEmail,
        public string $conflictingEmail,
        public SalesChannelContext $salesChannelContext,
        public UniqueConstraintViolationException $exception,
    ) {
    }
}

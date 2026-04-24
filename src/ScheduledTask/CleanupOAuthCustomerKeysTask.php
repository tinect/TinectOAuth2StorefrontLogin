<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupOAuthCustomerKeysTask extends ScheduledTask
{
    #[\Override]
    public static function getTaskName(): string
    {
        return 'tinect_oauth.cleanup_customer_keys';
    }

    #[\Override]
    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}

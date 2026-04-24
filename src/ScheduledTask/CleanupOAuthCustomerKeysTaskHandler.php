<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CleanupOAuthCustomerKeysTask::class)]
class CleanupOAuthCustomerKeysTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly Connection $connection,
        LoggerInterface $exceptionLogger
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    #[\Override]
    public function run(): void
    {
        $total = 0;
        do {
            $result = $this->connection->executeStatement(
                'DELETE FROM `tinect_oauth_storefront_customer_key`
                 WHERE `customer_id` IN (SELECT `id` FROM `customer` WHERE `active` = 0)
                 LIMIT 50'
            );
            $total += $result;
        } while ($result > 0);

        if ($total > 0) {
            $this->exceptionLogger->info('Removed {count} OAuth customer keys for inactive customers', ['count' => $total]);
        }
    }
}

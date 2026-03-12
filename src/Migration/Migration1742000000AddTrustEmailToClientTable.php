<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1742000000AddTrustEmailToClientTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1742000000;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'trust_email\''
        );

        if (empty($columns)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                ADD COLUMN `trust_email` TINYINT(1) NOT NULL DEFAULT 0
                    AFTER `connect_only`;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

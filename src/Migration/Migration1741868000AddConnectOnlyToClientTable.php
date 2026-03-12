<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1741868000AddConnectOnlyToClientTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1741868000;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'connect_only\''
        );

        if (empty($columns)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                ADD COLUMN `connect_only` TINYINT(1) NOT NULL DEFAULT 0
                    AFTER `active`;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

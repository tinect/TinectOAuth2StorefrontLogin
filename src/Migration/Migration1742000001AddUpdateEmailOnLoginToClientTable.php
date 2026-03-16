<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1742000001AddUpdateEmailOnLoginToClientTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1742000001;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'update_email_on_login\''
        );

        if (empty($columns)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                ADD COLUMN `update_email_on_login` TINYINT(1) NOT NULL DEFAULT 0;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

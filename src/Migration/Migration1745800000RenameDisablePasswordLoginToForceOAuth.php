<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1745800000RenameDisablePasswordLoginToForceOAuth extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1745800000;
    }

    public function update(Connection $connection): void
    {
        $hasOld = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'disable_password_login\''
        );

        if (!empty($hasOld)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                CHANGE COLUMN `disable_password_login` `force_o_auth` TINYINT(1) NOT NULL DEFAULT 0;
            ');

            return;
        }

        $hasNew = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'force_o_auth\''
        );

        if (empty($hasNew)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                ADD COLUMN `force_o_auth` TINYINT(1) NOT NULL DEFAULT 0;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1742600000AddHideLoginButtonToClientTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1742600000;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchFirstColumn(
            'SHOW COLUMNS FROM `tinect_oauth_storefront_client` LIKE \'hide_login_button\''
        );

        if (empty($columns)) {
            $connection->executeStatement('
                ALTER TABLE `tinect_oauth_storefront_client`
                ADD COLUMN `hide_login_button` TINYINT(1) NOT NULL DEFAULT 0;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

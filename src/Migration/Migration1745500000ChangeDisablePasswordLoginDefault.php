<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1745500000ChangeDisablePasswordLoginDefault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1745500000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `tinect_oauth_storefront_client`
            MODIFY COLUMN `disable_password_login` TINYINT(1) NOT NULL DEFAULT 1;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

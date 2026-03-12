<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1741737600CreateOAuthClientTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1741737600;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `tinect_oauth_storefront_client` (
                `id`         BINARY(16)   NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                `provider`   VARCHAR(64)  NOT NULL,
                `active`     TINYINT(1)   NOT NULL DEFAULT 0,
                `config`     JSON         NOT NULL,
                `created_at` DATETIME(3)  NOT NULL,
                `updated_at` DATETIME(3)  NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

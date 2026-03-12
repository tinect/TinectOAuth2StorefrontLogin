<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1741737601CreateOAuthCustomerKeyTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1741737601;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `tinect_oauth_storefront_customer_key` (
                `id`          BINARY(16)   NOT NULL,
                `customer_id` BINARY(16)   NOT NULL,
                `client_id`   BINARY(16)   NOT NULL,
                `primary_key` VARCHAR(256) NOT NULL,
                `created_at`  DATETIME(3)  NOT NULL,
                `updated_at`  DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.tinect_oauth_sk.client_primary` (`client_id`, `primary_key`),
                INDEX `idx.tinect_oauth_sk.customer_id` (`customer_id`),
                CONSTRAINT `fk.tinect_oauth_sk.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE CASCADE,
                CONSTRAINT `fk.tinect_oauth_sk.client_id`
                    FOREIGN KEY (`client_id`)
                    REFERENCES `tinect_oauth_storefront_client` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

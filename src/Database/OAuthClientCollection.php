<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Database;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<OAuthClientEntity>
 */
final class OAuthClientCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OAuthClientEntity::class;
    }
}

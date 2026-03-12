<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Contract;

use Shopware\Core\Framework\Struct\Struct;

final class User extends Struct
{
    public string $primaryKey = '';

    public string $primaryEmail = '';

    /**
     * @var string[]
     */
    public array $emails = [];

    public string $firstName = '';

    public string $lastName = '';
}

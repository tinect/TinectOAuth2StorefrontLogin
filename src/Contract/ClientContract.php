<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Contract;

use Shopware\Core\Framework\Struct\Struct;

abstract class ClientContract extends Struct
{
    abstract public function getLoginUrl(?string $state, RedirectBehaviour $behaviour): string;

    abstract public function getUser(string $state, string $code, RedirectBehaviour $behaviour): User;
}

<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Contract;

use Shopware\Core\Framework\Struct\Struct;

final class RedirectBehaviour extends Struct
{
    public function __construct(
        public bool $expectState = false,
        public string $codeKey = 'code',
        public string $stateKey = 'state',
        public ?string $redirectUri = null,
    ) {
    }
}

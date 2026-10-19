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

    /** @var string[] Email addresses explicitly verified by the identity service. */
    public array $verifiedEmails = [];

    public function isEmailVerified(string $email): bool
    {
        $normalize = static fn (string $value): string => strtolower(trim($value));

        return $email !== '' && \in_array($normalize($email), array_map($normalize, $this->verifiedEmails), true);
    }

    public string $firstName = '';

    public string $lastName = '';
}

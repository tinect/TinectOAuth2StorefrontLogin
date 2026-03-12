<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

class OAuthProviderNotConfiguredException extends OAuthException
{
    public function __construct(string $providerKey, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('OAuth provider "%s" is not configured correctly.', $providerKey), 0, $previous);
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.providerNotConfigured';
    }
}

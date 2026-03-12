<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

abstract class OAuthException extends \RuntimeException
{
    /**
     * Storefront snippet key to show to the customer.
     */
    abstract public function getSnippetKey(): string;

    /**
     * Parameters to interpolate into the snippet (e.g. ['%provider%' => 'GitHub']).
     *
     * @return array<string, string>
     */
    public function getSnippetParams(): array
    {
        return [];
    }
}

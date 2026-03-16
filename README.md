# TinectOAuth2StorefrontLogin

Adds OAuth2 / OpenID Connect login to the Shopware 6 storefront.
Customers can sign in with GitHub, Microsoft Entra ID, Google, or any OpenID Connect provider, and can connect or disconnect providers from their account profile.

## Requirements

- Shopware `~6.6.0||~6.7.0`

## Installation

```bash
composer require tinect/oauth2-storefront-login
bin/console plugin:install --activate TinectOAuth2StorefrontLogin
bin/console cache:clear
```

## Configuration

Open the Shopware Administration and navigate to **Settings → Plugins → OAuth Storefront Login**.

Create one entry per provider you want to offer:

| Field | Description |
|---|---|
| **Name** | Label shown on the login button (e.g. `GitHub`) |
| **Provider** | `GitHub`, `OpenID Connect`, `Microsoft Entra ID`, or `Google Mail` |
| **Active** | Toggle to enable/disable the button on the login page |
| **Connect only** | When enabled the provider will not create new customer accounts — it can only be used to link an existing account from the profile page |
| **Require email verification on login** | When enabled, a key-based login only succeeds if the email address returned by the provider also matches the linked customer account. Useful for providers that always supply a verified email (e.g. Google, Microsoft), and also when the shop owner needs to retain control over which email addresses are used, ensuring customers cannot bypass email policies through OAuth login. |
| **Update email address on every login** | When enabled, the customer's email address in Shopware is updated to match the provider's email on each login. Useful when the provider (e.g. corporate SSO) is the authoritative source for email addresses. |
| **Disable password login** | When enabled, customers who have a connected account with this provider can no longer log in using their email and password — they must use this OAuth provider instead. |
| **Hide login button** | When enabled, the login button for this provider is not shown on the storefront login page. The provider can still be used to connect accounts from the customer profile page. |

### GitHub

1. Go to GitHub → Settings → Developer settings → OAuth Apps → **New OAuth App**.
2. Set **Authorization callback URL** to `https://your-shop.example.com/account/oauth/{clientId}/callback`
   (replace `{clientId}` with the UUID shown in the admin after saving).
3. Copy **Client ID** and **Client Secret** into the plugin settings.

### OpenID Connect

| Field | Description |
|---|---|
| **Client ID** | Your OIDC client ID |
| **Client Secret** | Your OIDC client secret |
| **Discovery Document URL** | e.g. `https://accounts.google.com/.well-known/openid-configuration` — endpoints are fetched automatically when this is set |
| **Authorization / Token / Userinfo Endpoint** | Fill only if you are not using a discovery document |
| **Scopes** | Space-separated, defaults to `openid email profile` |

The callback URL to register with your provider is `https://your-shop.example.com/account/oauth/{clientId}/callback`.

## Login flow

```
Customer → "Continue with GitHub" button
    → GET /account/oauth/{clientId}          (store state + intent in session, redirect to provider)
    → provider authorization page
    → GET /account/oauth/{clientId}/callback (validate state, exchange code, resolve customer)
    → account home page
```

**Customer resolution order:**

1. Existing OAuth key mapping → login directly
   - If **Require email verification on login** is enabled: the key mapping is only accepted when the provider's email also matches the linked customer — prevents access if an OAuth key is reused by someone with a different email address
   - If **Update email address on every login** is enabled: the customer's email in Shopware is updated to the provider's email (only if different)
2. Active customer with matching e-mail → link and login
3. No match + registration allowed → register new customer, link, login
4. No match + `connectOnly` enabled → error, redirect to login page

## Administration

### OAuth Clients

Manage providers under **Settings → Plugins → OAuth Storefront Login**.

The **View Connections** button opens a dedicated list of all active customer–provider connections across the shop, showing customer number, name, email, provider name, provider type, and the date the connection was established. Connections can be removed directly from this list.

### Customer detail

Each customer's detail page shows a **Connected OAuth Providers** card listing all providers linked to that account. Connections can also be removed from there.

## Account connect / disconnect

Logged-in customers can manage connected providers on the **Account → Profile** page.
Each active provider is shown with a **Connect** or **Disconnect** button.

```
Customer → "Connect with GitHub" button
    → GET /account/oauth/{clientId}/connect           (_loginRequired, stores connect intent)
    → provider authorization page
    → GET /account/oauth/{clientId}/callback          (same endpoint as login, intent from session)
    → profile page (success flash)

Customer → "Disconnect" button
    → POST /account/oauth/{clientId}/disconnect       (_loginRequired)
    → profile page (success flash)
```

## Adding a custom provider

1. Create a class that extends `Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract`:

```php
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientProviderContract;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MyProviderClientProvider extends ClientProviderContract
{
    public function provides(): string
    {
        return 'my_provider';
    }

    public function getConfigurationTemplate(): OptionsResolver
    {
        $resolver = parent::getConfigurationTemplate();
        $resolver->setRequired(['clientId', 'clientSecret']);
        $resolver->setAllowedTypes('clientId', 'string');
        $resolver->setAllowedTypes('clientSecret', 'string');
        return $resolver;
    }

    public function provideClient(array $resolvedConfig): ClientContract
    {
        return new MyProviderClient($resolvedConfig);
    }
}
```

2. The class is auto-tagged via `_instanceof: ClientProviderContract` — no service registration needed.

3. Add an admin Vue.js component named `tinect-oauth-provider-my-provider-settings` (kebab-case of `my_provider`) to render the config fields, and import it in `main.js`.

## Routes

| Name                               | Path | Method |
|------------------------------------|---|---|
| `widgets.tinect.oauth.redirect`    | `/account/oauth/{clientId}` | GET |
| `widgets.tinect.oauth.connect`     | `/account/oauth/{clientId}/connect` | GET |
| `tinect.oauth.callback`            | `/account/oauth/{clientId}/callback` | GET |
| `widgets.tinect.oauth.disconnect`  | `/account/oauth/{clientId}/disconnect` | POST |

## Events

The plugin dispatches the following events that you can subscribe to:

| Event class | Fired when |
|---|---|
| `OAuthCustomerRegisteredEvent` | A new customer account was created via OAuth |
| `OAuthCustomerConnectedEvent` | A customer explicitly connected a provider from their profile |
| `OAuthCustomerDisconnectedEvent` | A customer disconnected a provider from their profile |
| `OAuthCustomerEmailUpdatedEvent` | A customer's email was updated on login (requires **Update email address on every login**) |
| `OAuthCustomerEmailUpdateConflictEvent` | Email update was skipped because the new email is already used by another account |

All events are in the `Tinect\OAuth2StorefrontLogin\Event` namespace.

## License

MIT

<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Twig;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Tinect\OAuth2StorefrontLogin\Service\ClientLoader;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OAuthTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ClientLoader $clientLoader,
        private readonly Connection $connection,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tinect_oauth_clients', [$this, 'getClients']),
            new TwigFunction('tinect_oauth_connected_clients', [$this, 'getConnectedClients']),
            new TwigFunction('tinect_oauth_clients_for_recovery_hash', [$this, 'getClientsForRecoveryHash']),
        ];
    }

    /**
     * @return array<array{id: string, name: string, provider: string}>
     */
    public function getClients(Context $context): array
    {
        $clients = $this->clientLoader->getActiveClients($context);
        $result = [];

        foreach ($clients as $client) {
            if ($client->hideLoginButton) {
                continue;
            }

            $result[] = [
                'id' => $client->id,
                'name' => $client->name ?? '',
                'provider' => $client->provider ?? '',
            ];
        }

        return $result;
    }

    /**
     * @return array<array{id: string, name: string, provider: string}>
     */
    public function getClientsForRecoveryHash(string $hash): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(cl.id)) as id, cl.name, cl.provider
             FROM customer_recovery cr
             INNER JOIN tinect_oauth_storefront_customer_key k ON k.customer_id = cr.customer_id
             INNER JOIN tinect_oauth_storefront_client cl ON cl.id = k.client_id
             WHERE cr.hash = :hash
               AND cl.disable_password_login = 1
               AND cl.active = 1',
            ['hash' => $hash],
        );
    }

    /**
     * Returns all active OAuth clients with a `connected` flag for the given customer.
     *
     * @return array<array{id: string, name: string, provider: string, connected: bool}>
     */
    public function getConnectedClients(Context $context, string $customerId): array
    {
        $clients = $this->clientLoader->getActiveClients($context);

        if ($clients->count() === 0) {
            return [];
        }

        $connectedClientIds = $this->connection->fetchFirstColumn(
            'SELECT LOWER(HEX(client_id))
             FROM tinect_oauth_storefront_customer_key
             WHERE customer_id = :customerId',
            ['customerId' => Uuid::fromHexToBytes($customerId)],
        );

        $result = [];
        foreach ($clients as $client) {
            $result[] = [
                'id' => $client->id,
                'name' => $client->name ?? '',
                'provider' => $client->provider ?? '',
                'connected' => \in_array($client->id, $connectedClientIds, true),
            ];
        }

        return $result;
    }
}

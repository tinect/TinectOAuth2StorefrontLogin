<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Tinect\OAuth2StorefrontLogin\Contract\ClientContract;
use Tinect\OAuth2StorefrontLogin\Contract\ClientFactory;
use Tinect\OAuth2StorefrontLogin\Database\OAuthClientCollection;
use Tinect\OAuth2StorefrontLogin\Database\OAuthClientEntity;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthClientNotFoundException;

final readonly class ClientLoader
{
    /**
     * @param EntityRepository<OAuthClientCollection> $clientsRepository
     */
    public function __construct(
        private EntityRepository $clientsRepository,
        private ClientFactory $clientFactory,
    ) {
    }

    public function load(string $clientId, Context $context, ?OAuthClientEntity $entity = null): ClientContract
    {
        $entity ??= $this->getEntity($clientId, $context);

        return $this->clientFactory->create($entity->provider ?? '', $entity->config ?? []);
    }

    public function getEntity(string $clientId, Context $context): OAuthClientEntity
    {
        $criteria = new Criteria([$clientId]);

        /** @var OAuthClientCollection $entities */
        $entities = $this->clientsRepository->search($criteria, $context)->getEntities();
        $entity = $entities->first();

        if (!$entity instanceof OAuthClientEntity) {
            throw new OAuthClientNotFoundException($clientId);
        }

        return $entity;
    }

    public function getActiveClients(Context $context): OAuthClientCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));

        /** @var OAuthClientCollection $entities */
        $entities = $this->clientsRepository->search($criteria, $context)->getEntities();

        return $entities;
    }
}

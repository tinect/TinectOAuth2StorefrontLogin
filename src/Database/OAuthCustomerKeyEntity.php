<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Database;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ForeignKey;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ManyToOne;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Entity as BaseEntity;

#[Entity('tinect_oauth_storefront_customer_key', collectionClass: OAuthCustomerKeyCollection::class)]
class OAuthCustomerKeyEntity extends BaseEntity
{
    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[ForeignKey(entity: 'customer')]
    public string $customerId;

    #[ForeignKey(entity: 'tinect_oauth_storefront_client')]
    public string $clientId;

    #[Field(type: FieldType::STRING)]
    public string $primaryKey;

    #[ManyToOne(entity: 'customer')]
    public ?CustomerEntity $customer = null;

    #[ManyToOne(entity: 'tinect_oauth_storefront_client')]
    public ?OAuthClientEntity $client = null;
}

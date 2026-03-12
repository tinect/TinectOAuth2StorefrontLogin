<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Database;

use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\OneToMany;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Entity as BaseEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

#[Entity('tinect_oauth_storefront_client', collectionClass: OAuthClientCollection::class)]
class OAuthClientEntity extends BaseEntity
{
    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[Field(type: FieldType::STRING)]
    public string $name;

    #[Field(type: FieldType::STRING)]
    public string $provider;

    #[Field(type: FieldType::BOOL)]
    public bool $active = false;

    #[Field(type: FieldType::BOOL)]
    public bool $connectOnly = false;

    #[Field(type: FieldType::BOOL)]
    public bool $trustEmail = false;

    #[Field(type: FieldType::BOOL)]
    public bool $updateEmailOnLogin = false;

    #[Field(type: FieldType::JSON)]
    public ?array $config = null;

    #[OneToMany(entity: 'tinect_oauth_storefront_customer_key', ref: 'client_id')]
    public ?EntityCollection $customerKeys = null;
}

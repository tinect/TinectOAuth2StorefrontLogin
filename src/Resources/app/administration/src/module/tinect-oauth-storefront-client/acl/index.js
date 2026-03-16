Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'settings',
    key: 'tinect_oauth_storefront_client',
    roles: {
        viewer: {
            privileges: [
                'tinect_oauth_storefront_client:read',
                'tinect_oauth_storefront_customer_key:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'tinect_oauth_storefront_client:update',
                'tinect_oauth_storefront_customer_key:delete',
            ],
            dependencies: ['tinect_oauth_storefront_client.viewer'],
        },
        creator: {
            privileges: ['tinect_oauth_storefront_client:create'],
            dependencies: ['tinect_oauth_storefront_client.editor'],
        },
        deleter: {
            privileges: ['tinect_oauth_storefront_client:delete'],
            dependencies: ['tinect_oauth_storefront_client.viewer'],
        },
    },
});

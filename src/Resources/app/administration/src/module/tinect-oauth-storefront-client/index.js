import './page/listing';
import './page/edit';

const { Module } = Shopware;

Module.register('tinect-oauth-storefront-client', {
    type: 'plugin',
    name: 'tinect-oauth-storefront-client.title',
    title: 'tinect-oauth-storefront-client.title',
    description: 'tinect-oauth-storefront-client.title',
    color: '#ff68b4',
    icon: 'regular-plug',

    routes: {
        list: {
            component: 'tinect-oauth-storefront-client-listing-page',
            path: 'list',
        },
        create: {
            component: 'tinect-oauth-storefront-client-edit-page',
            path: 'create',
        },
        edit: {
            component: 'tinect-oauth-storefront-client-edit-page',
            path: 'edit/:id',
            props: {
                default(route) {
                    return { clientId: route.params.id };
                },
            },
        },
    },

    settingsItem: [
        {
            name: 'tinect-oauth-storefront-client',
            to: 'tinect.oauth.storefront.client.list',
            label: 'tinect-oauth-storefront-client.title',
            group: 'plugins',
            icon: 'regular-plug',
        },
    ],
});

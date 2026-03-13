import './acl';
import './page/listing';
import './page/edit';
import './page/connections';

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
            meta: {
                privilege: 'tinect_oauth_storefront_client.viewer',
            },
        },
        create: {
            component: 'tinect-oauth-storefront-client-edit-page',
            path: 'create',
            meta: {
                privilege: 'tinect_oauth_storefront_client.creator',
            },
        },
        edit: {
            component: 'tinect-oauth-storefront-client-edit-page',
            path: 'edit/:id',
            meta: {
                privilege: 'tinect_oauth_storefront_client.editor',
            },
            props: {
                default(route) {
                    return { clientId: route.params.id };
                },
            },
        },
        connections: {
            component: 'tinect-oauth-storefront-connections-page',
            path: 'connections',
            meta: {
                privilege: 'tinect_oauth_storefront_client.viewer',
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
            privilege: 'tinect_oauth_storefront_client.viewer',
        },
    ],
});

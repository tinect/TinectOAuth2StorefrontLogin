import template from './tinect-oauth-customer-connected-providers.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('tinect-oauth-customer-connected-providers', {
    template,

    inject: ['acl', 'repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    props: {
        customerId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            connectedProviders: [],
            isLoading: false,
            showDeleteModal: false,
            deleteKeyId: null,
        };
    },

    computed: {
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },

        repository() {
            return this.repositoryFactory.create(
                'tinect_oauth_storefront_customer_key'
            );
        },

        columns() {
            return [
                {
                    property: 'client.name',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.customerDetail.columnName'
                    ),
                    primary: true,
                    allowResize: true,
                },
                {
                    property: 'client.provider',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.customerDetail.columnProvider'
                    ),
                    allowResize: true,
                },
                {
                    property: 'createdAt',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.customerDetail.columnConnectedSince'
                    ),
                    allowResize: true,
                },
            ];
        },
    },

    created() {
        this.loadProviders();
    },

    methods: {
        loadProviders() {
            this.isLoading = true;

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('customerId', this.customerId));
            criteria.addAssociation('client');
            criteria.addSorting(Criteria.sort('createdAt', 'ASC'));

            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.connectedProviders = result;
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        onDisconnect(keyId) {
            this.deleteKeyId = keyId;
            this.showDeleteModal = true;
        },

        onConfirmDisconnect() {
            this.showDeleteModal = false;
            this.isLoading = true;

            this.repository
                .delete(this.deleteKeyId, Shopware.Context.api)
                .then(() => {
                    this.deleteKeyId = null;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'tinect-oauth-storefront-client.customerDetail.disconnectSuccess'
                        ),
                    });
                    this.loadProviders();
                })
                .catch(() => {
                    this.isLoading = false;
                    this.deleteKeyId = null;
                    this.createNotificationError({
                        message: this.$tc(
                            'tinect-oauth-storefront-client.customerDetail.disconnectError'
                        ),
                    });
                });
        },

        onCancelDisconnect() {
            this.showDeleteModal = false;
            this.deleteKeyId = null;
        },

        providerLabel(provider) {
            const key = `tinect-oauth-storefront-client.provider.${provider}`;
            const translated = this.$tc(key);
            return translated !== key ? translated : provider;
        },
    },
});

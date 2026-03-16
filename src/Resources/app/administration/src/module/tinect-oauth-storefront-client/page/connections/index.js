import template from './tinect-oauth-storefront-connections-page.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('tinect-oauth-storefront-connections-page', {
    template,

    inject: ['acl', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            connections: null,
            isLoading: true,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            showDeleteModal: false,
            deleteConnectionId: null,
        };
    },

    computed: {
        repository() {
            return this.repositoryFactory.create('tinect_oauth_storefront_customer_key');
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },

        columns() {
            return [
                {
                    property: 'customer.customerNumber',
                    label: this.$tc('tinect-oauth-storefront-client.connections.columnCustomerNumber'),
                    allowResize: true,
                },
                {
                    property: 'customer.email',
                    label: this.$tc('tinect-oauth-storefront-client.connections.columnCustomer'),
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'client.name',
                    label: this.$tc('tinect-oauth-storefront-client.connections.columnClient'),
                    allowResize: true,
                },
                {
                    property: 'client.provider',
                    label: this.$tc('tinect-oauth-storefront-client.connections.columnProvider'),
                    allowResize: true,
                },
                {
                    property: 'createdAt',
                    label: this.$tc('tinect-oauth-storefront-client.connections.columnConnectedSince'),
                    allowResize: true,
                },
            ];
        },
    },

    created() {
        this.getList();
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addAssociation('client');
            criteria.addAssociation('customer');
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            if (this.term) {
                criteria.setTerm(this.term);
            }

            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.connections = result;
                    this.total = result.total;
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        onDeleteConnection(id) {
            this.deleteConnectionId = id;
            this.showDeleteModal = true;
        },

        onConfirmDelete() {
            this.showDeleteModal = false;
            this.isLoading = true;

            this.repository
                .delete(this.deleteConnectionId, Shopware.Context.api)
                .then(() => {
                    this.deleteConnectionId = null;
                    this.createNotificationSuccess({
                        message: this.$tc('tinect-oauth-storefront-client.connections.deleteSuccess'),
                    });
                    this.getList();
                })
                .catch(() => {
                    this.isLoading = false;
                    this.deleteConnectionId = null;
                    this.createNotificationError({
                        message: this.$tc('tinect-oauth-storefront-client.connections.deleteError'),
                    });
                });
        },

        onCancelDelete() {
            this.showDeleteModal = false;
            this.deleteConnectionId = null;
        },

        providerLabel(provider) {
            if (!provider) {
                return '';
            }
            const key = `tinect-oauth-storefront-client.provider.${provider}`;
            const translated = this.$tc(key);
            return translated !== key ? translated : provider;
        },

        customerLabel(customer) {
            if (!customer) {
                return '—';
            }
            const name = [customer.firstName, customer.lastName].filter(Boolean).join(' ');
            return name ? `${name} (${customer.email})` : customer.email;
        },
    },
});

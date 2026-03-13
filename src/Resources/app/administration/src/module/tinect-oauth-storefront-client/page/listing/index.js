import template from './tinect-oauth-storefront-client-listing-page.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('tinect-oauth-storefront-client-listing-page', {
    template,

    inject: ['acl', 'repositoryFactory'],

    mixins: [Mixin.getByName('listing')],

    data() {
        return {
            repository: null,
            clients: null,
            isLoading: true,
            sortBy: 'name',
            sortDirection: 'ASC',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.list.columnName'
                    ),
                    routerLink: 'tinect.oauth.storefront.client.edit',
                    primary: true,
                    allowResize: true,
                },
                {
                    property: 'provider',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.list.columnProvider'
                    ),
                    allowResize: true,
                },
                {
                    property: 'active',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.list.columnActive'
                    ),
                    allowResize: true,
                },
            ];
        },
    },

    created() {
        this.repository = this.repositoryFactory.create(
            'tinect_oauth_storefront_client'
        );
        this.getList();
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            if (this.term) {
                criteria.setTerm(this.term);
            }

            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.clients = result;
                    this.total = result.total;
                    this.isLoading = false;
                });
        },

        onCreateNew() {
            this.$router.push({
                name: 'tinect.oauth.storefront.client.create',
            });
        },

        onShowConnections() {
            this.$router.push({
                name: 'tinect.oauth.storefront.client.connections',
            });
        },
    },
});

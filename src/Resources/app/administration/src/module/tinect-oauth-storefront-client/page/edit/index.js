import template from './tinect-oauth-storefront-client-edit-page.html.twig';

const { Component, Mixin } = Shopware;

Component.register('tinect-oauth-storefront-client-edit-page', {
    template,

    inject: ['acl', 'repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    props: {
        clientId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            client: null,
            isLoading: false,
            isSaveSuccessful: false,
            showDeleteModal: false,
            providerOptions: [
                {
                    value: 'github',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.provider.github'
                    ),
                },
                {
                    value: 'open_id_connect',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.provider.open_id_connect'
                    ),
                },
                {
                    value: 'microsoft_entra',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.provider.microsoft_entra'
                    ),
                },
                {
                    value: 'google_mail',
                    label: this.$tc(
                        'tinect-oauth-storefront-client.provider.google_mail'
                    ),
                },
            ],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        isCreateMode() {
            return !this.clientId;
        },

        repository() {
            return this.repositoryFactory.create(
                'tinect_oauth_storefront_client'
            );
        },

        providerSettingsComponent() {
            if (!this.client || !this.client.provider) {
                return null;
            }
            return `tinect-oauth-provider-${this.client.provider.replace(/_/g, '-')}-settings`;
        },
    },

    watch: {
        clientId(newId) {
            if (newId) {
                this.loadClient();
            }
        },

        'client.trustEmail'(value) {
            if (value && this.client) {
                this.client.updateEmailOnLogin = false;
            }
        },
    },

    created() {
        if (this.isCreateMode) {
            this.client = this.repository.create(Shopware.Context.api);
            this.client.active = false;
            this.client.connectOnly = false;
            this.client.trustEmail = false;
            this.client.updateEmailOnLogin = false;
            this.client.disablePasswordLogin = true;
            this.client.hideLoginButton = false;
            this.client.config = {};
        } else {
            this.loadClient();
        }
    },

    methods: {
        loadClient() {
            this.isLoading = true;
            this.repository
                .get(this.clientId, Shopware.Context.api)
                .then((client) => {
                    this.client = client;
                    if (!this.client.config) {
                        this.client.config = {};
                    }
                    this.isLoading = false;
                });
        },

        onSave() {
            if (
                this.isCreateMode &&
                (!this.client.name || !this.client.provider)
            ) {
                return;
            }

            this.isLoading = true;
            this.isSaveSuccessful = false;

            this.repository
                .save(this.client, Shopware.Context.api)
                .then(() => {
                    if (this.isCreateMode) {
                        this.isLoading = false;
                        this.$router.push({
                            name: 'tinect.oauth.storefront.client.edit',
                            params: { id: this.client.id },
                        });
                        return;
                    }
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'tinect-oauth-storefront-client.notification.saveSuccess'
                        ),
                    });
                })
                .then(() => {
                    return this.loadClient();
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'tinect-oauth-storefront-client.notification.saveError'
                        ),
                    });
                });
        },

        onDelete() {
            this.showDeleteModal = false;
            this.isLoading = true;
            this.repository
                .delete(this.clientId, Shopware.Context.api)
                .then(() => {
                    this.$router.push({
                        name: 'tinect.oauth.storefront.client.list',
                    });
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'tinect-oauth-storefront-client.notification.deleteError'
                        ),
                    });
                });
        },
    },
});

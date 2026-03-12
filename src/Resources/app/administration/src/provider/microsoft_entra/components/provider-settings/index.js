import template from './tinect-oauth-provider-microsoft-entra-settings.html.twig';

const { Component } = Shopware;

Component.register('tinect-oauth-provider-microsoft-entra-settings', {
    template,

    props: {
        item: {
            required: true,
        },
    },

    computed: {
        callbackUrl() {
            return `${window.location.origin}/account/oauth/${this.item.id}/callback`;
        },
    },
});

import template from './tinect-oauth-provider-google-mail-settings.html.twig';

const { Component } = Shopware;

Component.register('tinect-oauth-provider-google-mail-settings', {
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

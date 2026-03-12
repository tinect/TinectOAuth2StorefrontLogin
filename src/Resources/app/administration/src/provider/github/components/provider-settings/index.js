import template from './tinect-oauth-provider-github-settings.html.twig';

const { Component } = Shopware;

Component.register('tinect-oauth-provider-github-settings', {
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

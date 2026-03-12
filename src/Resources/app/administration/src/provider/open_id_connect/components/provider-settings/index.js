import template from './tinect-oauth-provider-open-id-connect-settings.html.twig';

const { Component } = Shopware;

Component.register('tinect-oauth-provider-open-id-connect-settings', {
    template,

    props: {
        item: {
            required: true,
        },
    },
});

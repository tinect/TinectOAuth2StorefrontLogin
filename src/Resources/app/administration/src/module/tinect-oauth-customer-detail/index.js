import './components/tinect-oauth-customer-connected-providers';

import overrideTemplate from './sw-customer-detail-base.html.twig';

Shopware.Component.override('sw-customer-detail-base', {
    template: overrideTemplate,

    inject: ['acl'],
});

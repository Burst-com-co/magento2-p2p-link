define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'link',
                component: 'Burst_Link/js/view/payment/method-renderer/link'
            }
        );
        return Component.extend({});
    }
);
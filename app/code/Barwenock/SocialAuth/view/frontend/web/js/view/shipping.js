define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function ($, wrapper, quote, customer) {
    'use strict';

    return function (Component) {
        return Component.extend({
            defaults: {
                shippingFormTemplate: 'Barwenock_SocialAuth/shipping.html'
            },

            onAfterRenderFunc: function () {
                if (window.checkoutConfig.isCustomerLoggedIn) {
                    this.isFieldLoaded(this.renderField);
                }
            },

            isFieldLoaded: function (callback) {
                var field = $("#shipping-new-address-form");
                setTimeout(function () {
                    if (field.find('[name="firstname"]').length > 0) {
                        callback();
                    } else {
                        this.isFieldLoaded(callback);
                    }
                }.bind(this), 50);
            },

            renderField: function () {
                var customerData = customer.customerData;
                var form = $("#shipping-new-address-form");
                var firstName = form.find('[name="firstname"]');
                var lastName = form.find('[name="lastname"]');

                if (!firstName.val()) {
                    firstName.val(customerData.firstname).keyup();
                }
                if (!lastName.val()) {
                    lastName.val(customerData.lastname).keyup();
                }
            }
        });
    };
});

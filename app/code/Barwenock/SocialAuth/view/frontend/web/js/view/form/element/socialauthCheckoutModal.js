define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/action/check-email-availability',
    'Magento_Customer/js/action/login',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/validation'
], function (
    $,
    Component,
    ko,
    customer,
    checkEmailAvailability,
    loginAction,
    quote,
    checkoutData,
    alert,
    fullScreenLoader,
    globalMessageList,
    $t
) {
    'use strict';

    var socialauthConfig = window.checkoutConfig.socialauthConfiguration;
    var isCustomerLoggedIn = window.checkoutConfig.isCustomerLoggedIn;

    return Component.extend({
        defaults: {
            template: 'Barwenock_SocialAuth/form/element/socialauthCheckoutModal',
        },
        initialize: function () {
            var self = this;
            this._super().initObservable();
            this.showMessage();
            this.authorize();
            this.facebookAuthorize();
        },
        customerSession: window.checkoutConfig.isCustomerLoggedIn,
        moduleStatus:  socialauthConfig.moduleStatus,
        isCheckoutOn:  socialauthConfig.isCheckoutOn,
        status: socialauthConfig.socialStatus,
        facebookStatus: socialauthConfig.facebookStatus,
        googleStatus: socialauthConfig.googleStatus,
        twitterStatus: socialauthConfig.twitterStatus,
        linkedinStatus: socialauthConfig.linkedinStatus,
        instagramStatus: socialauthConfig.instagramStatus,
        fbAppId: socialauthConfig.facebookAppId,
        uId: socialauthConfig.facebookUserId,
        localeCode: socialauthConfig.localeCode,
        fbLoginUrl: socialauthConfig.facebookAuthUrl,
        facebookAuthIcon: socialauthConfig.facebookAuthIcon,
        twitterAuthIcon: socialauthConfig.twitterAuthIcon,
        googleAuthIcon: socialauthConfig.googleAuthIcon,
        linkedinAuthIcon: socialauthConfig.linkedinAuthIcon,
        instagramAuthIcon: socialauthConfig.instagramAuthIcon,
        options: {
            twitterLogin: '#twitter-modal-auth',
            linkedinLogin: '#linkedin-modal-auth',
            googleLogin: '#google-modal-auth',
            instagramLogin: '#instagram-modal-auth',
            fbLogin: '#facebook-modal-auth',
            actionButton :'.login .actions-toolbar,.create .actions-toolbar',
            socialContainer : ".socialauth_container"
        },

        authorize: function () {
            var self = this;
            var socialPlatforms = ['twitter', 'linkedin', 'google', 'instagram'];

            socialPlatforms.forEach(function (platform) {
                $('body').on('click', self.options[platform + 'Login'], function (e) {
                    var url = socialauthConfig.popupConfiguration[platform + 'Url'];
                    var width = socialauthConfig.popupConfiguration.width;
                    var height = socialauthConfig.popupConfiguration.height;
                    self.openSocialAuthPopup(url, width, height);
                });
            });
        },

        openSocialAuthPopup: function (url, width, height) {
            var url = url + 'checkoutPage/1';
            var screenX = typeof window.screenX !== 'undefined' ? window.screenX : window.screenLeft;
            var screenY = typeof window.screenY !== 'undefined' ? window.screenY : window.screenTop;
            var outerWidth = typeof window.outerWidth !== 'undefined' ? window.outerWidth : document.body.clientWidth;
            var outerHeight = typeof window.outerHeight !== 'undefined' ? window.outerHeight : document.body.clientHeight - 22;
            var left = parseInt(screenX + (outerWidth - width) / 2, 10);
            var top = parseInt(screenY + (outerHeight - height) / 2.5, 10);
            var scroller = 1;
            var settings = 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',scrollbars=' + scroller;
            var newwindow = window.open(url, '', settings);
            if (window.focus) {
                newwindow.focus();
            }
            return false;
        },

        facebookAuthorize: function () {
            var self = this;
            $(self.options.actionButton).append($(self.options.socialContainer));
            window.fbAsyncInit = function () {
                FB.init({
                    appId: socialauthConfig.facebookAppId,
                    status: true,
                    cookie: true,
                    xfbml: true,
                    oauth: true
                });
            };
            (function (d) {
                var js, id = 'facebook-jssdk';
                if (d.getElementById(id)) {
                    return;
                }
                js = d.createElement('script');
                js.id = id;
                js.async = true;
                js.src = '//connect.facebook.net/' + socialauthConfig.localeCode + '/all.js';
                d.getElementsByTagName('head')[0].appendChild(js);
            }(document));
            $('body').on('click', self.options.fbLogin, function (e) {
                self.login();
            });
        },

        login: function () {
            var self = this;
            FB.login(function (response) {
                if (response.status == 'connected') {
                    document.location.href = socialauthConfig.facebookAuthUrl + 'checkoutPage/1';
                } else {
                    // User is not logged in
                    window.location.reload();
                }
            }, { scope: 'email' });
            return false;
        },

        showMessage: function () {
            var messageContainer = messageContainer || globalMessageList;
            $.ajax({
                url: socialauthConfig.getMessagesUrl,
                type: 'GET',
                success: function (response) {
                    if (response.errorMsg) {
                        messageContainer.addErrorMessage({
                            message: response.errorMsg
                        });
                    }
                    if (response.successMsg) {
                        messageContainer.addSuccessMessage({
                            message: response.successMsg
                        });
                    }
                },
                error: function (response) {
                    alert({
                        content: response
                    });
                }
            });
        }
    });
});

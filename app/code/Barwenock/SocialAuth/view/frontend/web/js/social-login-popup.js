define([
    "jquery",
], function ($) {
    'use strict';
    $.widget('mage.socialLoginPopup', {
        options: {
            twitterLogin: '.twitter',
            linkedinLogin: '.linkedin',
            googleLogin: '.google',
            instagramLogin: '.instagram'
        },

        _create: function () {
            let self = this;
            $(self.options.twitterLogin).on('click', function (e) {
                self.openSocialAuthPopup(self.options.twitterUrl,self.options.width,self.options.height);
            });
            $(self.options.linkedinLogin).on('click', function (e) {
                self.openSocialAuthPopup(self.options.linkedinUrl,self.options.width,self.options.height);
            });
            $(self.options.googleLogin).on('click', function (e) {
                self.openSocialAuthPopup(self.options.googleUrl,self.options.width,self.options.height);
            });
            $(self.options.instagramLogin).on('click', function (e) {
                self.openSocialAuthPopup(self.options.instagramUrl,self.options.width,self.options.height);
            });
        },

        openSocialAuthPopup: function (url, width, height) {
            let screenX = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft;
            let screenY = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop;
            let outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth;
            let outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22);
            let left = parseInt(screenX + ((outerWidth - width) / 2), 10);
            let top = parseInt(screenY + ((outerHeight - height) / 2.5), 10);
            let scroller = 1;
            let settings = (
                'width=' + width +
                ',height=' + height +
                ',left=' + left +
                ',top=' + top +
                ',scrollbars=' + scroller
            );

            let newWindow = window.open(url, '', settings);
            if (window.focus) {
                newWindow.focus()
            }
            return false;
        }
    });
    return $.mage.socialLoginPopup;
});

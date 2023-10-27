define([
    "jquery",
    'mage/translate',
    "jquery/ui"
], function ($, $t, alert) {
    'use strict';
    $.widget('mage.fbBlockPlugin', {
        options: {
            fbLogin: '.fblogin',
            actionButton :'.login .actions-toolbar .create .actions-toolbar',
            socialContainer : ".socialauth_container"
        },

        _create: function () {
            var self = this;
            $(self.options.actionButton).append($(self.options.socialContainer));
            window.fbAsyncInit = function () {
                FB.init({
                    appId: self.options.fbAppId,
                    status     : true,
                    cookie     : true,
                    xfbml      : true,
                    oauth      : true
                });
                FB.getLoginStatus(function (response) {
                    if (response.status == 'connected') {
                        if (self.options.customerSession && self.options.uId) {
                            self.greet(self.options.uId);
                        }
                    }
                });
            };
            (function (d) {
                var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {
                    return;}
                js = d.createElement('script'); js.id = id; js.async = true;
                js.src = "//connect.facebook.net/"+self.options.localeCode+"/all.js";
                d.getElementsByTagName('head')[0].appendChild(js);
            }(document));
            $(self.options.fbLogin).on('click', function (e) {
                self.fblogin();
            });
        },

        greet: function (id) {
            FB.api('/me', function (response) {
                var src = 'https://graph.facebook.com/'+id+'/picture';
                $('.welcome-msg')[0].insert('<img height="20" src="'+src+'"/>');
            });
        },

        login: function () {
            var self = this;
            document.location.href=self.options.fbLoginUrl;
        },

        fblogin: function () {
            var self = this;
            FB.login(function (response) {
                if (response.status == 'connected') {
                    self.login();
                } else {
                    // user is not logged in
                    window.location.reload();
                }
            }, {scope:'email'});
            return false;
        }
    });
    return $.mage.fbBlockPlugin;
});

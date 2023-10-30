define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        window.authenticationPopup = config.serializedConfig;
        window.authenticationPopupConfiguration = config.socialsConfiguration;
    }
});

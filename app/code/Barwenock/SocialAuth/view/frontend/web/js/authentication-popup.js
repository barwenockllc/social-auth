<!--
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */
 -->
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        window.authenticationPopup = config.serializedConfig;
        window.authenticationPopupConfiguration = config.socialsConfiguration;
    }
});

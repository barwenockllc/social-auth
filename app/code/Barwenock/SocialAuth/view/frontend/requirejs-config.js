<!--
/**
 * @author Barwenock
 * @copyright Copyright (c) Barwenock
 * @package Social Authorizes for Magento 2
 */
 -->
var config = {
    map: {
        '*': {
            fbBlockPlugin: 'Barwenock_SocialAuth/js/fb-block-plugin',
            socialLoginPopup: 'Barwenock_SocialAuth/js/social-login-popup'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Barwenock_SocialAuth/js/view/shipping': true
            }
        }
    }

};

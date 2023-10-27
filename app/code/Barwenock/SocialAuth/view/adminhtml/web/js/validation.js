require([
    "jquery",
    "Magento_Ui/js/modal/alert"
], function ($, alert) {
    $(document).ready(function () {
        $('.input-file').on('change', function () {
            var fileInput = this;
            var filePath = fileInput.value;
            // Allowed file types
            var allowedExtensions = /\.(jpg|jpeg|png|gif)$/i;
            if (!allowedExtensions.test(filePath)) {
                alert({
                    content: "Please upload files in '.jpg', '.jpeg', '.png', or '.gif' formats only."
                });
                fileInput.value = '';
            }
        });
    });
});

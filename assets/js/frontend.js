jQuery(document).ready(function ($) {
    $('.scm-tab').on('click', function () {
        const target = $(this).data('target');

        // Toggle active tab
        $('.scm-tab').removeClass('active');
        $(this).addClass('active');

        // Toggle tab content
        $('.scm-tab-content').removeClass('active');
        $('#' + target).addClass('active');
    });
});

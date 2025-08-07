jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();

        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.crcm-tab-content').hide();
        $(target).show();
    });
});

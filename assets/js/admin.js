/**
 * Admin utilities for Custom Rental Car Manager.
 *
 * @package CustomRentalCarManager
 * @since 1.0.0
 */

(function($){
    'use strict';

    const CRCM_Admin = {
        init() {
            this.setupMenu();
            this.toggleMetaboxes();
        },

        setupMenu() {
            $('#toplevel_page_crcm').addClass('current');
        },

        toggleMetaboxes() {
            $('.crcm-metabox .crcm-metabox-title').on('click', function(){
                $(this).next('.crcm-metabox-content').slideToggle();
            });
        }
    };

    $(document).ready(() => CRCM_Admin.init());

})(jQuery);

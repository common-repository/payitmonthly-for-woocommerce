// (function ($) {
//     'use strict';
//
//     /**
//      * All of the code for your public-facing JavaScript source
//      * should reside in this file.
//      *
//      * Note: It has been assumed you will write jQuery code here, so the
//      * $ function reference has been prepared for usage within the scope
//      * of this function.
//      *
//      * This enables you to define handlers, for when the DOM is ready:
//      *
//      * $(function() {
//      *
//      * });
//      *
//      * When the window is loaded:
//      *
//      * $( window ).load(function() {
//      *
//      * });
//      *
//      * ...and/or other possibilities.
//      *
//      * Ideally, it is not considered best practise to attach more than a
//      * single DOM-ready or window-load handler for a particular page.
//      * Although scripts in the WordPress core, Plugins and Themes may be
//      * practising this, we should strive to set a better example in our own work.
//      */
//
//
//     $(window).load(function () {
//
//
//
//
//         const display_success_notif = (message) =>{
//             const success = $("#pim_success_notif")
//             const error = $("#pim_error_notif")
//
//             success.text( message)
//             success.show()
//             error.hide()
//             success.text()
//             alert(message)
//
//             $('body').scrollTo(success);
//         }
//
//
//         $("#pim_deactivat_all").click(function (e) {
//             e.preventDefault();
//             $.ajax({
//                 url: ajaxurl,
//                 dataType: "json",
//                 data: {
//                     action: "pim_handle_control_buttons",
//                     mode: 1,
//                     _ajax_nonce: $("#pim_deactivat_all").attr("nonce")
//                 },
//                 method: 'POST',
//                 success: function (response) {
//                     display_success_notif(`Success: ${response.changed} disabled from ${response.total} products`)
//                 },
//                 error: function (error) {
//                     console.log(error)
//                 }
//             })
//         });
//
//         $("#pim_enable_all").click(function (e) {
//             e.preventDefault();
//             $.ajax({
//                 url: ajaxurl,
//                 dataType: "json",
//                 data: {
//                     action: "pim_handle_control_buttons",
//                     mode: 2,
//                     _ajax_nonce: $("#pim_enable_all").attr("nonce")
//                 },
//                 method: 'POST',
//                 success: function (response) {
//                     display_success_notif(`Success: ${response.changed} enabled from ${response.total} products`)
//                 },
//                 error: function (error) {
//                     console.log(error)
//                 }
//             })
//         });
//
//
//     });
//
// })(jQuery);

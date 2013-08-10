/**
 * Created with JetBrains PhpStorm.
 * User: willemjan
 * Date: 8/10/13
 * Time: 10:47 AM
 * To change this template use File | Settings | File Templates.
 */
(function($){
    "use strict";

    $('section[role="navigation"] .main > li').mouseenter(function(e) {
        var name = $(this).data('name'),
            menu = $('#submenu-' + name);

        $('.sub').hide();

        if (menu.find('> li').length > 0) {
            menu.show();
        }
    });

    $('section[role="navigation"]').mouseleave(function(e) {
        $('.submenus > ul').hide();
    });

})(jQuery);
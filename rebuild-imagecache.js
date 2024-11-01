/*
 * This is a part of WP-LatestPhotos plugin
 * Description: Image cache rebuild
 * Author: Andrew Mihaylov
 * URI: http://www.codeispoetry.ru/
 * $Id: rebuild-imagecache.js 197935 2010-01-25 16:12:27Z andddd $
 */

jQuery(document).ready(function($){

    try{convertEntities(wpLatestPhotosL10n);}catch(e){};

    var completed = 0;
    var total = 0;

    var n = $('#wp_latestphotos_ajax input[type=hidden]');
    var posturl = ajaxurl + '?' +
        n.eq(0).attr('name') + '=' + n.eq(0).val() + '&' +
        n.eq(1).attr('name') + '=' + n.eq(1).val();
    
    var data = { action: 'rebuild_imagecache', subaction: 'count' };

    $('.wrap h2').after('<div id="wp_latestphotos_status" class="updated fade"><p><img src="' + wpLatestPhotosL10n.pluginURL + '/ajax-loader.gif" alt="" style="margin-right: 5px;" />' + wpLatestPhotosL10n.pleaseWait + ' <span>0/0</span></p></div>');

    $.post(posturl, data, function(response) {
        total = parseInt(response);
        if(total > 0){
            $('#wp_latestphotos_status span').text(completed + '/' + total);
            rebuild();
        } else {
            finish();
        }
    });

    function finish() {
        $('#wp_latestphotos_status p').html('<strong>' + wpLatestPhotosL10n.cacheRebuilt + '</strong>');
    }

    function rebuild() {
        data['subaction'] = 'process';

        $.post(posturl, data, function(response) {
                var i = parseInt(response);

                if(i > 0) {
                    completed += i;
                    $('#wp_latestphotos_status span').text(completed + '/' + total);

                    if(completed >= total) {
                        finish();
                    } else {
                        setTimeout(function(){ rebuild(); }, 1000);
                    }
                }
                else if(i == -4) {
                    finish();
                } else {
                    $('#wp_latestphotos_status p').html('<strong>' + wpLatestPhotosL10n.unknownError + ' ' + response + '. ' + wpLatestPhotosL10n.tryAgain + '</strong>');
                }
            });
    }
    
});

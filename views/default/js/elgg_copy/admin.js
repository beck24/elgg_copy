define(['require', 'jquery', 'elgg'], function(require, $, elgg) {

    $('.elgg-copy-trigger').click(function(e) {

        e.preventDefault();

        if (!confirm('This action will reset this instance to a current copy of the target. Are you sure?')) {
            return false;
        }

        $('body').colorbox({
            modal : true,
            html : '<div style="min-height:150px;width:400px;"><h2>Resetting environment!</h2>'
                      + '<br /><br />This process may take some time to complete depending on the amount of data required to sync.</div>'
        });

        var url = $(this).attr('href');

        $.ajax({
            url: url,
            timeout: 72000000 // 20 hrs, walk away and leave it
        })
        .done(function() {
            //alert( "success" );
        })
        .fail(function() {
            //alert( "error" );
        })
        .always(function() {
            window.location = elgg.get_site_url();
        });
    });
});

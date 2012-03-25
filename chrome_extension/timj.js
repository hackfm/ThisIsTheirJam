(function ($) {
    var TMIJ_URL = 'http://localhost/mha/timj.php';

    var username = $('#meAvatar').attr('alt'),
        url      = TMIJ_URL + '?username=' + username;



    $.getJSON(url, function (response) {
        for (i=0;i<response.usernames.length;++i) {
            response.usernames[i] = '<a href="http://www.thisismyjam.com/'+response.usernames[i]+'">'+response.usernames[i]+'</a>';
        }
        response.users = response.usernames.join(', ');
        $.tmpl(
            '<div class="layer">\
                <div class="container">\
                    <h1>Jam this if you are only after "loves": <a href="?search=${artist} ${track}">${artist} - ${track}</a></h1>\
                    \
                    <p>\
                        We\'ve taken a look at your followers, checked out their\
                        jams and loved tracks and come up with this song which\
                        might be very well received by {{html users}}.\
                        \
                        \
                    </p>\
                </div>\
            </div>',
            response
        ).insertAfter('#createJamTop');
    });
})(jQuery);

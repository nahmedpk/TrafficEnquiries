jQuery(document).ready(function ($) {
    $('.expand-one').on('click', function () {
        $(this).find('.content-one').slideToggle('slow');
    });
    var $msg = $('.traffic_container .msg');
    $('#traffic_form').on('submit', function (e) {
        e.preventDefault();
        var data_strings = $('#traffic_form').serialize() + "&method=save_form";
        $msg.html('');
        $.ajax({
            type: 'post',
            url: traffic.plugin_url + '/modules/ajax_calls.php',
            dataType: "json",
            data: data_strings,
            success: function (data) {
                if (data.response == 'success') {
                    $msg.toggleClass('bg-success');
                    $msg.html('<p class="bg-success">' + data.message + '</p>');
                    setTimeout(window.location.reload.bind(window.location), 250);
                }
                else if (data.response == 'error') {
                    $msg.toggleClass('bg-danger');
                    $msg.html('<p class="bg-danger">' + data.error + '</p>');
                }
                else {
                    $msg.toggleClass('bg-warning');
                    console.log('invalid');
                }
            }
        });
    });
});
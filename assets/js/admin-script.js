jQuery(document).ready(function($) {
    $('#cj_pre_roll_video__saveButton').click(function() {
        var _container = $('#cj_pre_roll_video'),
            _enabled = _container.find('#enable_pre_roll'),
            _time = _container.find('#time_to_allow_skip_pre_roll'),
            _input = _container.find('#commercial_video'),
            _spinner = _container.find('span.spinner'),
            _result = _container.find('.cj_pre_roll_video__result');

        _spinner.addClass('is-active');
        _result.stop().slideUp(200).removeClass('s e').html('');

        var sendData = {};
        sendData.action = 'cj_pre_roll_video__save';
        sendData.nonce = $(this).data('nonce');
        sendData.enabled = _enabled.is(':checked') ? 1 : 0;
        sendData.time = _time.val();
        sendData.url = _input.val();

        $.post(ajaxurl, sendData, function(data) {
            if (data.status == 'success') {
                _result.addClass('s').html('<p>' + data.message + '</p>');
                if (data.video_src) {
                    _result.append('<video controls><source src="' + data.video_src + '" type="video/mp4"></video>');
                }
                _result.stop().slideDown(200);
            } else if (data.status == 'error') {
                _result.addClass('e').html(data.message);
                _result.stop().slideDown(200);
            } else {
                console.log(data);
            }

            _spinner.removeClass('is-active');
        }, 'json');
    });
});
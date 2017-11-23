/*(function($) {
    $.fn.codja_pre_roll_video = function(options) {
        var defaults = {

        };

        var settings = $.extend({}, defaults, options);

        return this.each(function() {

        });
    };

    function debug( obj ) {
        if ( window.console && window.console.log ) {
            window.console.log( "hilight selection count: " + obj.length );
        }
    };

})(jQuery);*/

jQuery(document).ready(function($) {
    $('.codja_pre_roll_video').each(function(index, element) {
        var _this = $(this),
            _video_provider = _this.data('videoProvider'),
            _video_id = _this.data('videoId');

        var thumbnail = codja_pre_roll_set_video_thumbnail(_this, _video_provider, _video_id);

        _this.find('video').on('contextmenu', function() {
            return false;
        });
    });

    function codja_pre_roll_set_video_thumbnail(element, provider, video_id) {
        if (provider == 'youtube') {
            $(element).find('video').attr('poster', 'https://img.youtube.com/vi/' + video_id + '/maxresdefault.jpg');
        } else if (provider == 'vimeo') {
            $.getJSON('https://vimeo.com/api/v2/video/' + video_id + '.json', {}, function(data) {
                $(element).find('video').attr('poster', data[0]['thumbnail_large']);
            });
        }
    }

    $('.codja_pre_roll_video_play').click(function() {
        var _this = $(this),
            _player = _this.closest('.codja_pre_roll_video'),
            video = _player.find('video');

        video.get(0).play();
        _this.hide();

        var _mute = _player.find('.codja_pre_roll_video_mute');
        _mute.show();

        _mute.on('click', function() {
            if ($(this).hasClass('is-active')) {
                video.prop('muted', false);
                $(this).removeClass('is-active');
            } else {
                video.prop('muted', true);
                $(this).addClass('is-active');
            }
        });

        var _skip = _player.find('.codja_pre_roll_video_skip');
        _skip.text(cj_pre_roll_settings.time_to_skip);
        _skip.data('remaining', cj_pre_roll_settings.time_to_skip);

        _skip.show();

        var skipTimer;
        skipTimer = setInterval(function() {
            var remaining = _skip.data('remaining');

            if (remaining == 1) {
                _skip.data('remaining', 0);
                _skip.data('allowSkip', 1);

                _skip.text(cj_pre_roll_settings.texts.skip_button);
                _skip.addClass('is-active');

                clearInterval(skipTimer);
            } else {
                _skip.data('remaining', remaining - 1);
                _skip.text(remaining - 1);
            }
        }, 1000);

        _player.on('click', '.codja_pre_roll_video_skip.is-active', function() {
            var _this = $(this);

            if (_this.data('remaining') == 0 && _this.data('allowSkip') == 1) {
                codja_pre_roll_replace_iframe(_player);
            }
        });

        video.on('ended', function() {
            codja_pre_roll_replace_iframe(_player);
        });
    });

    function codja_pre_roll_replace_iframe(_player) {
        var _box = _player;

        var provider = _box.data('videoProvider'),
            iframe = $(_box.data('iframe'));

        var iframe_src = iframe.attr('src');

        if (iframe_src.indexOf('?') >= 0) {
            iframe_src = iframe_src + '&autoplay=1';
        } else {
            iframe_src = iframe_src + '?autoplay=1';
        }

        iframe.attr('src', iframe_src);

        _box.replaceWith(iframe);
    }
});
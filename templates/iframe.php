<?php

    $styles = array();
    if (isset($video_data['width']) && $video_data['width'] != false) {
        $styles[] = 'max-width: ' . $video_data['width'] . 'px';
    }
    if (isset($video_data['height']) && $video_data['height'] != false) {
        $styles[] = 'max-height: ' . $video_data['height'] . 'px';
    }

    $style = !empty($styles) ? 'style="' . implode('; ', $styles) . '"' : '';

?>
<div class="codja_pre_roll_video" <?php echo $style; ?> data-video-id="<?php echo $video_data['video_id']; ?>" data-video-provider="<?php echo $video_data['provider']; ?>" data-iframe='<?php echo $iframe; ?>'>
    <div class="codja_pre_roll_video_elements">
        <div class="codja_pre_roll_video_play"></div>
        <div class="codja_pre_roll_video_skip"></div>
        <div class="codja_pre_roll_video_mute"></div>
    </div>
    <video class="pre_roll_video" preload="none" poster="">
        <source src="<?php echo $video_src; ?>" type="video/mp4">
    </video>
</div>
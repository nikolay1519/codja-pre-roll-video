<?php

    $settings = get_post_meta($post->ID, 'cj_pre_roll_settings', true);

    if ($settings == false) {
        $settings = array(
            'disabled' => 0,
            'url' => ''
        );
    }

?>
<p>
    <label><input type="checkbox" name="cj_pre_roll_settings[disable]" value="1" <?php if ($settings['disabled']) echo 'checked'; ?> /> <?php _e('Disable Pre-Roll for this post', 'cj-pre-roll'); ?></label>
    <input type="hidden" name="cj_pre_roll_settings[nonce]" value="<?php echo wp_create_nonce('update_cj_pre_roll_settings_for_post_'.$post->ID); ?>" />
</p>
<p>
    <lable for="cj_pre_roll_settings_commercial_video"><strong>Pre-Roll video for this post</strong></lable>
    <input style="width: 99%" type="text" id="cj_pre_roll_settings_commercial_video" name="cj_pre_roll_settings[commercial_video]" value="<?php echo $settings['url']; ?>" />
    <p class="description"><?php _e('Supported links:', 'cj-pre-roll'); ?><br/>— Youtube: https://www.youtube.com/watch?v=R59TevgzN3k<br/>— Vimeo: https://vimeo.com/56567020</p>
</p>
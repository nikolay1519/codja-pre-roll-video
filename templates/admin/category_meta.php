<h2><?php _e('Pre-Roll Video Settings', 'cj-pre-roll'); ?></h2>
<?php

    $settings = get_term_meta($term->term_id, 'cj_pre_roll_settings', true);

    if ($settings == false) {
        $settings = array(
            'disabled' => 0,
            'url' => ''
        );
    }

?>
<table class="form-table">
    <tr>
        <th scope="row"><label for="cj_pre_roll_disable"><?php _e('Disable Pre-Roll for this category', 'cj-pre-roll'); ?></label></th>
        <td>
            <input type="checkbox" name="cj_pre_roll_settings[disable]" id="cj_pre_roll_disable" value="1" <?php if ($settings['disabled']) echo 'checked'; ?> />
            <input type="hidden" name="cj_pre_roll_settings[nonce]" value="<?php echo wp_create_nonce('update_cj_pre_roll_settings_for_category_'.$term->term_id); ?>" />
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="cj_pre_roll_commercial_video"><?php _e('Pre-Roll video for this category', 'cj-pre-roll'); ?></label></th>
        <td>
            <input id="cj_pre_roll_commercial_video" name="cj_pre_roll_settings[commercial_video]" type="text" value="<?php echo $settings['url']; ?>" class="regular-text code">
            <p class="description"><?php _e('Supported links:', 'cj-pre-roll'); ?><br/>— Youtube: https://www.youtube.com/watch?v=R59TevgzN3k<br/>— Vimeo: https://vimeo.com/56567020</p>
        </td>
    </tr>
</table>
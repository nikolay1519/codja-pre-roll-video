<div id="cj_pre_roll_video" class="wrap">
    <h1><?php _e('Pre-Roll Video Settings', 'cj-pre-roll'); ?></h1>

    <table class="form-table">
        <tr>
            <th scope="row"><label for="enable_pre_roll"><?php _e('Enable Pre-Roll', 'cj-pre-roll'); ?></label></th>
            <td><input type="checkbox" id="enable_pre_roll" value="1" <?php if ($this->settings['enabled']) echo 'checked'; ?>></td>
        </tr>
        <tr>
            <th scope="row"><label for="time_to_allow_skip_pre_roll"><?php _e('Time to allow skip pre-roll', 'cj-pre-roll'); ?></label></th>
            <td>
                <input type="text" id="time_to_allow_skip_pre_roll" value="<?php echo $this->settings['time_to_skip']; ?>" class="small-text">
                <p class="description"><?php _e('The time in seconds until the skip pre-roll button appears. If the value is 0, the button will appear immediately.', 'cj-pre-roll'); ?></p>
            </td>

        </tr>
        <tr>
            <th scope="row"><label for="commercial_video"><?php _e('Default pre-roll video', 'cj-pre-roll'); ?></label></th>
            <td>
                <input id="commercial_video" type="text" value="<?php echo $this->settings['url']; ?>" class="regular-text code">
                <p class="description"><?php _e('Supported links:', 'cj-pre-roll'); ?><br/>— Youtube: https://www.youtube.com/watch?v=R59TevgzN3k<br/>— Vimeo: https://vimeo.com/56567020</p>
            </td>
        </tr>
    </table>
    <div class="cj_pre_roll_video__result"></div>
    <span id="cj_pre_roll_video__saveButton" class="button button-primary" data-nonce="<?php echo wp_create_nonce('cj_pre_roll_video_save'); ?>"><?php _e('Save', 'cj-pre-roll'); ?></span>
    <span class="spinner"></span>
</div>
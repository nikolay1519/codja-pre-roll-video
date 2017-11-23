<?php

    /**
     * Plugin Name: CODJA Pre-Roll Video
     * Description: Allow add ads video to each video in post
     * Version: 1.0.0
     * Author: CODJA
     * Text Domain: cj-pre-roll
     * Domain Path: /languages/
     */

    if (!defined( 'ABSPATH')) {
        exit;
    }

    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    if (!class_exists('Codja_Pre_Roll_Video')) {
        define('CJ_PRE_ROLL_VIDEO_VERSION', '1.0');
        define('CJ_PRE_ROLL_VIDEO_DIR', plugin_dir_path(__FILE__));
        define('CJ_PRE_ROLL_VIDEO_URL', plugin_dir_url(__FILE__));

        register_activation_hook(__FILE__, array('Codja_Pre_Roll_Video', 'activation'));
        register_deactivation_hook(__FILE__, array('Codja_Pre_Roll_Video', 'deactivation'));
        register_uninstall_hook(__FILE__, array('Codja_Pre_Roll_Video', 'uninstall'));

        class Codja_Pre_Roll_Video {

            private $settings = array();
            private $ads_video_src = array();

            private static $instance = null;

            public static function getInstance() {
                if (null === self::$instance) {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            private function __clone() {}

            private function __construct() {
                load_plugin_textdomain('cj-pre-roll', false, basename(dirname(__FILE__)) .'/languages');

                if (is_admin()) {
                    if (defined('DOING_AJAX') && DOING_AJAX) {
                        add_action('wp_ajax_cj_pre_roll_video__save', array($this, 'ajaxSave'));
                    } else {
                        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScript'));
                        add_action('admin_menu', array($this, 'adminMenu'));

                        // Settings for categories
                        add_action('category_edit_form', array($this, 'metaForCategories'), 1);
                        add_action("edited_category", array($this, 'saveMetaForCategories'));

                        // Settings for posts
                        add_action('add_meta_boxes', array($this, 'metaForPosts'));
                        add_action('save_post', array($this, 'saveMetaForPosts'));
                    }
                } else {
                    $this->loadSettings();

                    add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
                    add_filter('the_content', array($this, 'the_content'));
                }
            }

            public function wp_enqueue_scripts() {
                if (is_singular('post')) {
                    if (is_rtl()) {
                        wp_enqueue_style('cj-pre-roll-video-styles', CJ_PRE_ROLL_VIDEO_URL . 'assets/css/cj-pre-roll-video-rtl.css');
                    } else {
                        wp_enqueue_style('cj-pre-roll-video-styles', CJ_PRE_ROLL_VIDEO_URL . 'assets/css/cj-pre-roll-video.css');
                    }

                    wp_enqueue_script('cj-pre-roll-video-script', CJ_PRE_ROLL_VIDEO_URL . 'assets/js/cj-pre-roll-video.js', array('jquery'), false, true);

                    // Print plugin js settings obj
                    $obj = array(
                        'time_to_skip' => $this->settings['time_to_skip'],
                        'texts' => array(
                            'skip_button' => __('Skip →', 'cj-pre-roll')
                        )
                    );
                    wp_localize_script('cj-pre-roll-video-script', 'cj_pre_roll_settings', $obj);
                }
            }

            public function the_content($content) {
                if ($this->settings['enabled'] == false || !is_singular('post')) {
                    return $content;
                }

                ob_start();
                $content = preg_replace_callback('#<iframe.*?></iframe>#is', array($this, 'replace_iframe'), $content);
                echo $content;
                return ob_get_clean();
            }

            private function replace_iframe($matches) {
                ob_start();
                $iframe = $matches[0];

                global $post;
                $ads_video_url = $this->getVideoSrcForPost($post);

                // If ads video not found return current iframe
                if ($ads_video_url == false) {
                    return $iframe;
                }

                // Video data of main video
                $video_data = $this->getVideoDataFromIframe($iframe);

                // Src of ads video
                $video_src = $this->getVideoSrc($this->getVideoDataFromUrl($ads_video_url));

                // If the src of ads video was not received, return current iframe
                if ($video_src == false) return $iframe;

                require(CJ_PRE_ROLL_VIDEO_DIR . 'templates/iframe.php');
                return ob_get_clean();
            }

            private function getVideoSrcForPost($post) {
                if (!isset($this->ads_video_src[$post->ID])) {
                    // Try to get ads video for current post
                    $settings = get_post_meta($post->ID, 'cj_pre_roll_settings', true);

                    if ($settings != false) {
                        // If plugin is disabled for current post, return false
                        if ($settings['disabled']) {
                            $this->ads_video_src[$post->ID] = false;
                            return false;
                        }

                        if ($settings['url'] != false) {
                            $this->ads_video_src[$post->ID] = $settings['url'];
                            return $settings['url'];
                        }
                    }

                    // Try to get category of the post
                    $categories = get_the_category($post->ID);
                    // Check only first category
                    $category_id = $categories[0]->term_id;
                    $settings = get_term_meta($category_id, 'cj_pre_roll_settings', true);

                    if ($settings != false) {
                        // If plugin is disabled for category of current post, return false
                        if ($settings['disabled']) {
                            $this->ads_video_src[$post->ID] = false;
                            return false;
                        }

                        if ($settings['url'] != false) {
                            $this->ads_video_src[$post->ID] = $settings['url'];
                            return $settings['url'];
                        }
                    }

                    $this->ads_video_src[$post->ID] = $this->settings['url'];
                    return $this->settings['url'];
                } else {
                    return $this->ads_video_src[$post->ID];
                }
            }

            private function getVideoDataFromIframe($iframe) {
                $video_data = array();

                if (preg_match('#youtube.com\/embed/(.{11})#i', $iframe, $matches)) {
                    $video_data['provider'] = 'youtube';
                    $video_data['video_id'] = $matches[1];
                } elseif (preg_match('#vimeo.com/video/([0-9]{9})#i', $iframe, $matches)) {
                    $video_data['provider'] = 'vimeo';
                    $video_data['video_id'] = $matches[1];
                }

                if (preg_match('#width="([0-9]*?)"#i', $iframe, $matches)) {
                    $video_data['width'] = $matches[1];
                }

                if (preg_match('#height="([0-9]*?)"#i', $iframe, $matches)) {
                    $video_data['height'] = $matches[1];
                }

                return $video_data;
            }

            private function getVideoDataFromUrl($url) {
                $video_data = array();

                if (preg_match('#v=(.{11})#i', $url, $matches)) {
                    $video_data['provider'] = 'youtube';
                    $video_data['video_id'] = $matches[1];
                } elseif (preg_match('#vimeo.com/(video/)?([0-9]+)#i', $url, $matches)) {
                    $video_data['provider'] = 'vimeo';
                    $video_data['video_id'] = $matches[2];
                }

                return $video_data;
            }

            private function getVideoSrc($video_data) {
                if ($video_data['provider'] == 'youtube') {
                    $src = $this->getYoutubeVideoSrc($video_data['video_id']);

                    if ($src != false) return $src;
                } elseif ($video_data['provider'] == 'vimeo') {
                    $src = $this->getVimeoVideoSrc($video_data['video_id']);

                    if ($src != false) return $src;
                }

                return false;
            }

            private function getYoutubeVideoSrc($video_id) {
                $request = wp_remote_get('http://www.youtube.com/get_video_info?video_id='.$video_id, array('timeout' => 3, 'httpversion' => '1.1'));

                if (!is_wp_error( $request ) || wp_remote_retrieve_response_code($request) === 200){
                    parse_str($request['body'], $info);

                    if ($info['status'] == 'ok') {
                        $info = explode(',' ,$info['url_encoded_fmt_stream_map']);
                        parse_str($info[0], $stream);

                        $url = urldecode($stream['url']);

                        list($server, $params) = explode('?', $url);
                        $url = 'https://redirector.googlevideo.com/videoplayback?'.$params;

                        return $url;
                    }
                }

                return false;
            }

            private function getVimeoVideoSrc($video_id) {
                $request = wp_remote_get('https://player.vimeo.com/video/' . $video_id . '/config', array('timeout' => 3, 'httpversion' => '1.1'));

                if (!is_wp_error( $request ) || wp_remote_retrieve_response_code($request) === 200){
                    $info = json_decode($request['body']);

                    if ($info->view == 1) {
                        return $info->request->files->progressive[count($info->request->files->progressive) - 1]->url;
                    }
                }

                return false;
            }

            public function metaForPosts($post_type) {
                add_meta_box('cj_pre_roll_settings', __('Pre-Roll Video Settings', 'cj-pre-roll'), array($this, 'renderMetaForPost'), 'post', 'side', 'default');
            }

            public function renderMetaForPost($post) {
                //print_r($post);
                require(CJ_PRE_ROLL_VIDEO_DIR . 'templates/admin/post_meta.php');
            }

            public function saveMetaForPosts($post_id) {
                if (!current_user_can('edit_post', $post_id)) return false;
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false;
                if (!isset($_POST['cj_pre_roll_settings']) || !isset($_POST['cj_pre_roll_settings']['nonce'])) return false;
                if (!wp_verify_nonce($_POST['cj_pre_roll_settings']['nonce'], 'update_cj_pre_roll_settings_for_post_' . $post_id)) return false;

                $disabled = isset($_POST['cj_pre_roll_settings']['disable']) ? 1 : 0;
                $url = isset($_POST['cj_pre_roll_settings']['commercial_video']) ? sanitize_text_field($_POST['cj_pre_roll_settings']['commercial_video']) : '';

                update_post_meta($post_id, 'cj_pre_roll_settings', array('disabled' => $disabled, 'url' => $url));
            }

            public function metaForCategories($term) {
                require(CJ_PRE_ROLL_VIDEO_DIR . 'templates/admin/category_meta.php');
            }

            public function saveMetaForCategories($term_id) {
                if (!current_user_can('edit_term', $term_id)) return false;
                if (!isset($_POST['cj_pre_roll_settings']) || !isset($_POST['cj_pre_roll_settings']['nonce'])) return false;
                if (!wp_verify_nonce($_POST['cj_pre_roll_settings']['nonce'], 'update_cj_pre_roll_settings_for_category_'.$term_id)) return false;

                $disabled = isset($_POST['cj_pre_roll_settings']['disable']) ? 1 : 0;
                $url = isset($_POST['cj_pre_roll_settings']['commercial_video']) ? sanitize_text_field($_POST['cj_pre_roll_settings']['commercial_video']) : '';

                update_term_meta($term_id, 'cj_pre_roll_settings', array('disabled' => $disabled, 'url' => $url));
            }

            public function adminMenu() {
                add_options_page(
                    __('Pre-Roll Video', 'cj-pre-roll'),
                    __('Pre-Roll Video', 'cj-pre-roll'),
                    'manage_options',
                    'cj-pre-roll',
                    array($this, 'renderPreRollPage')
                );
            }

            public function renderPreRollPage() {
                $this->loadSettings();
                require(CJ_PRE_ROLL_VIDEO_DIR . 'templates/admin/settings_page.php');
            }

            public function ajaxSave() {
                if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'cj_pre_roll_video_save')) {
                    $this->jsonDie(array('status' => 'access_error'));
                }

                $enabled = isset($_POST['enabled']) ? boolval($_POST['enabled']) : 0;
                $time = isset($_POST['time']) ? intval($_POST['time']) : 10;
                $url = isset($_POST['url']) ? sanitize_text_field($_POST['url']) : '';

                if ($enabled) {
                    $video_data = $this->getVideoDataFromUrl($url);

                    if ($video_data == false) {
                        $this->jsonDie(array('status' => 'error', 'message' => __('Unidentified link. Only Youtube and Vimeo links are supported.', 'cj-pre-roll')));
                    }

                    $video_src = $this->getVideoSrc($video_data);

                    if ($video_src == false) {
                        $this->jsonDie(array('status' => 'error', 'message' => __('Failed to get video link.', 'cj-pre-roll')));
                    }

                    $this->updateSettings(array('enabled' => intval($enabled), 'time_to_skip' => $time, 'url' => $url));
                    $this->jsonDie(array('status' => 'success', 'message' => __('Settings successfully saved. Commercial video:', 'cj-pre-roll'), 'video_src' => $video_src));
                }

                $this->updateSettings(array('enabled' => intval($enabled), 'time_to_skip' => $time, 'url' => $url));
                $this->jsonDie(array('status' => 'success', 'message' => __('Settings successfully saved', 'cj-pre-roll')));
            }

            public function adminEnqueueScript($hook_suffix) {
                if ($hook_suffix == 'settings_page_cj-pre-roll') {
                    wp_enqueue_style('cj-pre-roll-video-admin-styles', CJ_PRE_ROLL_VIDEO_URL . 'assets/css/admin-styles.css');
                    wp_enqueue_script('cj-pre-roll-video-admin-script', CJ_PRE_ROLL_VIDEO_URL . 'assets/js/admin-script.js', array('jquery'), null, true);
                }
            }

            private function updateSettings($settings) {
                update_option('cj_pre_roll_settings', $settings);
                $this->settings = $settings;
            }

            private function loadSettings() {
                $this->settings = $this->getSettings();
            }

            private function getSettings() {
                return get_option('cj_pre_roll_settings');
            }

            private function jsonDie($array) {
                wp_die(json_encode($array));
            }

            public static function activation() {
                if (!current_user_can('activate_plugins')) return;

                $defaultSettings = array(
                    'enabled' => 0,
                    'time' => 10,
                    'url' => ''
                );

                add_option('cj_pre_roll_settings', $defaultSettings);
            }

            public static function deactivation() {}

            public static function uninstall() {
                if (!current_user_can('activate_plugins')) return;

                delete_option('cj_pre_roll_settings');
            }
        }

        Codja_Pre_Roll_Video::getInstance();
    }
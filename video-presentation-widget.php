<?php
/**
 * Plugin Name: Video Presentation Widget
 * Plugin URI: https://github.com/yourusername/video-presentation-widget
 * Description: A professional multi-video presentation widget with GitHub-based automatic updates
 * Version: 2.0.0
 * Author: Joy Roy
 * Author URI: https://strativ.se
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: video-presentation-widget
 * Domain Path: /languages
 * Update URI: https://github.com/yourusername/video-presentation-widget
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VPW_VERSION', '2.0.0');
define('VPW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VPW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VPW_PLUGIN_FILE', __FILE__);

// GitHub repository details for updates
define('VPW_GITHUB_USERNAME', 'yourusername'); // Change this
define('VPW_GITHUB_REPO', 'video-presentation-widget'); // Change this

/**
 * Main Plugin Class
 */
class Video_Presentation_Widget {
    
    private $videos_option_name = 'vpw_videos';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'render_widget'));
        
        // AJAX handlers
        add_action('wp_ajax_vpw_save_video', array($this, 'ajax_save_video'));
        add_action('wp_ajax_vpw_delete_video', array($this, 'ajax_delete_video'));
        add_action('wp_ajax_vpw_reorder_videos', array($this, 'ajax_reorder_videos'));
        add_action('wp_ajax_vpw_get_video', array($this, 'ajax_get_video'));
        
        // GitHub updater
        require_once VPW_PLUGIN_DIR . 'includes/class-vpw-github-updater.php';
        if (is_admin()) {
            new VPW_GitHub_Updater(VPW_PLUGIN_FILE, VPW_GITHUB_USERNAME, VPW_GITHUB_REPO);
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Video Widget', 'video-presentation-widget'),
            __('Video Widget', 'video-presentation-widget'),
            'manage_options',
            'video-presentation-widget',
            array($this, 'videos_page'),
            'dashicons-video-alt3',
            30
        );
        
        add_submenu_page(
            'video-presentation-widget',
            __('Manage Videos', 'video-presentation-widget'),
            __('Manage Videos', 'video-presentation-widget'),
            'manage_options',
            'video-presentation-widget',
            array($this, 'videos_page')
        );
        
        add_submenu_page(
            'video-presentation-widget',
            __('Settings', 'video-presentation-widget'),
            __('Settings', 'video-presentation-widget'),
            'manage_options',
            'video-presentation-widget-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('vpw_settings_group', 'vpw_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'vpw_general_section',
            __('General Settings', 'video-presentation-widget'),
            array($this, 'general_section_callback'),
            'video-presentation-widget-settings'
        );
        
        // Widget Enable/Disable
        add_settings_field(
            'vpw_enable',
            __('Enable Widget', 'video-presentation-widget'),
            array($this, 'enable_field_callback'),
            'video-presentation-widget-settings',
            'vpw_general_section'
        );
        
        // Visibility Settings
        add_settings_field(
            'vpw_visibility',
            __('Widget Visibility', 'video-presentation-widget'),
            array($this, 'visibility_field_callback'),
            'video-presentation-widget-settings',
            'vpw_general_section'
        );
        
        // Style Settings Section
        add_settings_section(
            'vpw_style_section',
            __('Widget Style Settings', 'video-presentation-widget'),
            array($this, 'style_section_callback'),
            'video-presentation-widget-settings'
        );
        
        // Primary Color
        add_settings_field(
            'vpw_primary_color',
            __('Primary Color', 'video-presentation-widget'),
            array($this, 'primary_color_field_callback'),
            'video-presentation-widget-settings',
            'vpw_style_section'
        );
        
        // Widget Size
        add_settings_field(
            'vpw_widget_size',
            __('Widget Size', 'video-presentation-widget'),
            array($this, 'widget_size_field_callback'),
            'video-presentation-widget-settings',
            'vpw_style_section'
        );
        
        // Modal Dimensions
        add_settings_field(
            'vpw_modal_dimensions',
            __('Modal Dimensions', 'video-presentation-widget'),
            array($this, 'modal_dimensions_field_callback'),
            'video-presentation-widget-settings',
            'vpw_style_section'
        );
        
        // Position
        add_settings_field(
            'vpw_position',
            __('Widget Position', 'video-presentation-widget'),
            array($this, 'position_field_callback'),
            'video-presentation-widget-settings',
            'vpw_style_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enable'] = isset($input['enable']) ? 1 : 0;
        $sanitized['visibility_type'] = sanitize_text_field($input['visibility_type']);
        $sanitized['visibility_pages'] = isset($input['visibility_pages']) ? array_map('absint', $input['visibility_pages']) : array();
        $sanitized['visibility_posts'] = isset($input['visibility_posts']) ? array_map('absint', $input['visibility_posts']) : array();
        $sanitized['primary_color'] = $this->sanitize_hex_color_field($input['primary_color']);
        $sanitized['widget_size'] = absint($input['widget_size']);
        $sanitized['modal_width'] = absint($input['modal_width']);
        $sanitized['modal_height'] = absint($input['modal_height']);
        $sanitized['position'] = sanitize_text_field($input['position']);
        
        return $sanitized;
    }
    
    /**
     * Helper function to sanitize hex color
     */
    private function sanitize_hex_color_field($color) {
        // Use WordPress function if available
        if (function_exists('sanitize_hex_color')) {
            return sanitize_hex_color($color);
        }
        
        // Fallback sanitization
        $color = ltrim($color, '#');
        
        if (strlen($color) == 6) {
            return '#' . $color;
        } elseif (strlen($color) == 3) {
            return '#' . $color;
        }
        
        return '#fdba74'; // Default color
    }
    
    /**
     * Get all videos
     */
    public function get_videos() {
        $videos = get_option($this->videos_option_name, array());
        if (!is_array($videos)) {
            $videos = array();
        }
        return $videos;
    }
    
    /**
     * Save video via AJAX
     */
    public function ajax_save_video() {
        try {
            check_ajax_referer('vpw_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
                return;
            }
            
            $video_id = isset($_POST['video_id']) ? sanitize_text_field($_POST['video_id']) : '';
            
            $video_data = array(
                'title' => sanitize_text_field($_POST['title']),
                'cta_text' => sanitize_text_field($_POST['cta_text']),
                'video_type' => sanitize_text_field($_POST['video_type']),
                'video_url' => esc_url_raw($_POST['video_url']),
                'cover_image' => esc_url_raw($_POST['cover_image']),
                'button_text' => sanitize_text_field($_POST['button_text']),
                'button_url' => esc_url_raw($_POST['button_url']),
                'button_bg_color' => $this->sanitize_hex_color_field($_POST['button_bg_color']),
                'button_text_color' => $this->sanitize_hex_color_field($_POST['button_text_color']),
                'button_hover_bg' => $this->sanitize_hex_color_field($_POST['button_hover_bg']),
                'button_hover_text' => $this->sanitize_hex_color_field($_POST['button_hover_text']),
                'enabled' => isset($_POST['enabled']) ? 1 : 0,
            );
            
            $videos = $this->get_videos();
            
            if (empty($video_id)) {
                // New video
                $video_id = 'video_' . time() . '_' . wp_rand(1000, 9999);
                $video_data['id'] = $video_id;
                $video_data['order'] = count($videos);
                $videos[$video_id] = $video_data;
            } else {
                // Update existing
                if (isset($videos[$video_id])) {
                    $video_data['id'] = $video_id;
                    $video_data['order'] = $videos[$video_id]['order'];
                    $videos[$video_id] = $video_data;
                } else {
                    wp_send_json_error(array('message' => 'Video not found'));
                    return;
                }
            }
            
            update_option($this->videos_option_name, $videos);
            
            wp_send_json_success(array(
                'message' => 'Video saved successfully',
                'video' => $video_data
            ));
            
        } catch (Exception $e) {
            error_log('VPW ajax_save_video error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Delete video via AJAX
     */
    public function ajax_delete_video() {
        try {
            check_ajax_referer('vpw_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
                return;
            }
            
            if (!isset($_POST['video_id'])) {
                wp_send_json_error(array('message' => 'Video ID missing'));
                return;
            }
            
            $video_id = sanitize_text_field($_POST['video_id']);
            $videos = $this->get_videos();
            
            if (isset($videos[$video_id])) {
                unset($videos[$video_id]);
                update_option($this->videos_option_name, $videos);
                wp_send_json_success(array('message' => 'Video deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Video not found'));
            }
            
        } catch (Exception $e) {
            error_log('VPW ajax_delete_video error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Reorder videos via AJAX
     */
    public function ajax_reorder_videos() {
        try {
            check_ajax_referer('vpw_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
                return;
            }
            
            if (!isset($_POST['order']) || !is_array($_POST['order'])) {
                wp_send_json_error(array('message' => 'Order data missing'));
                return;
            }
            
            $order = $_POST['order'];
            $videos = $this->get_videos();
            
            foreach ($order as $index => $video_id) {
                if (isset($videos[$video_id])) {
                    $videos[$video_id]['order'] = $index;
                }
            }
            
            update_option($this->videos_option_name, $videos);
            wp_send_json_success(array('message' => 'Order updated'));
            
        } catch (Exception $e) {
            error_log('VPW ajax_reorder_videos error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Get single video via AJAX
     */
    public function ajax_get_video() {
        try {
            // Verify nonce
            check_ajax_referer('vpw_nonce', 'nonce');
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
                return;
            }
            
            // Get video ID
            if (!isset($_POST['video_id'])) {
                wp_send_json_error(array('message' => 'Video ID missing'));
                return;
            }
            
            $video_id = sanitize_text_field($_POST['video_id']);
            
            // Get all videos
            $videos = $this->get_videos();
            
            // Check if video exists
            if (!isset($videos[$video_id])) {
                wp_send_json_error(array('message' => 'Video not found: ' . $video_id));
                return;
            }
            
            // Get the video
            $video = $videos[$video_id];
            
            // Make sure all required fields exist with defaults
            $video_data = array(
                'id' => isset($video['id']) ? $video['id'] : $video_id,
                'title' => isset($video['title']) ? $video['title'] : '',
                'cta_text' => isset($video['cta_text']) ? $video['cta_text'] : '',
                'video_type' => isset($video['video_type']) ? $video['video_type'] : 'upload',
                'video_url' => isset($video['video_url']) ? $video['video_url'] : '',
                'cover_image' => isset($video['cover_image']) ? $video['cover_image'] : '',
                'button_text' => isset($video['button_text']) ? $video['button_text'] : '',
                'button_url' => isset($video['button_url']) ? $video['button_url'] : '',
                'button_bg_color' => isset($video['button_bg_color']) ? $video['button_bg_color'] : '#fdba74',
                'button_text_color' => isset($video['button_text_color']) ? $video['button_text_color'] : '#1a1a1a',
                'button_hover_bg' => isset($video['button_hover_bg']) ? $video['button_hover_bg'] : '#fb923c',
                'button_hover_text' => isset($video['button_hover_text']) ? $video['button_hover_text'] : '#000000',
                'enabled' => isset($video['enabled']) ? $video['enabled'] : 1,
                'order' => isset($video['order']) ? $video['order'] : 0
            );
            
            // Return success
            wp_send_json_success(array('video' => $video_data));
            
        } catch (Exception $e) {
            // Log the error
            error_log('VPW ajax_get_video error: ' . $e->getMessage());
            
            // Return error
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Videos management page
     */
    public function videos_page() {
        $videos = $this->get_videos();
        // Sort by order
        uasort($videos, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        include VPW_PLUGIN_DIR . 'templates/admin-videos.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('vpw_settings_group');
                do_settings_sections('video-presentation-widget-settings');
                submit_button(__('Save Settings', 'video-presentation-widget'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Field callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the general settings for your video presentation widget.', 'video-presentation-widget') . '</p>';
    }
    
    public function style_section_callback() {
        echo '<p>' . __('Customize the appearance of your video widget.', 'video-presentation-widget') . '</p>';
    }
    
    public function enable_field_callback() {
        $options = get_option('vpw_settings');
        $checked = isset($options['enable']) && $options['enable'] ? 'checked' : '';
        echo '<label><input type="checkbox" name="vpw_settings[enable]" value="1" ' . $checked . '> ' . __('Enable the video widget on your site', 'video-presentation-widget') . '</label>';
    }
    
    public function visibility_field_callback() {
        $options = get_option('vpw_settings');
        $visibility_type = isset($options['visibility_type']) ? $options['visibility_type'] : 'all';
        $visibility_pages = isset($options['visibility_pages']) ? $options['visibility_pages'] : array();
        $visibility_posts = isset($options['visibility_posts']) ? $options['visibility_posts'] : array();
        ?>
        <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; border-radius: 5px;">
            <p><strong><?php _e('Where should the widget appear?', 'video-presentation-widget'); ?></strong></p>
            
            <p>
                <label>
                    <input type="radio" name="vpw_settings[visibility_type]" value="all" <?php checked($visibility_type, 'all'); ?>>
                    <?php _e('Show on all pages', 'video-presentation-widget'); ?>
                </label>
            </p>
            
            <p>
                <label>
                    <input type="radio" name="vpw_settings[visibility_type]" value="specific_pages" <?php checked($visibility_type, 'specific_pages'); ?>>
                    <?php _e('Show on specific pages only', 'video-presentation-widget'); ?>
                </label>
            </p>
            
            <div id="vpw_specific_pages" style="margin-left: 25px; <?php echo $visibility_type !== 'specific_pages' ? 'display:none;' : ''; ?>">
                <label><?php _e('Select Pages:', 'video-presentation-widget'); ?></label><br>
                <select name="vpw_settings[visibility_pages][]" multiple style="width: 100%; max-width: 400px; height: 150px;">
                    <?php
                    $pages = get_pages();
                    foreach ($pages as $page) {
                        $selected = in_array($page->ID, $visibility_pages) ? 'selected' : '';
                        echo '<option value="' . $page->ID . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple pages', 'video-presentation-widget'); ?></p>
            </div>
            
            <p>
                <label>
                    <input type="radio" name="vpw_settings[visibility_type]" value="specific_posts" <?php checked($visibility_type, 'specific_posts'); ?>>
                    <?php _e('Show on specific posts only', 'video-presentation-widget'); ?>
                </label>
            </p>
            
            <div id="vpw_specific_posts" style="margin-left: 25px; <?php echo $visibility_type !== 'specific_posts' ? 'display:none;' : ''; ?>">
                <label><?php _e('Select Posts:', 'video-presentation-widget'); ?></label><br>
                <select name="vpw_settings[visibility_posts][]" multiple style="width: 100%; max-width: 400px; height: 150px;">
                    <?php
                    $posts = get_posts(array('numberposts' => -1, 'post_status' => 'publish'));
                    foreach ($posts as $post) {
                        $selected = in_array($post->ID, $visibility_posts) ? 'selected' : '';
                        echo '<option value="' . $post->ID . '" ' . $selected . '>' . esc_html($post->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple posts', 'video-presentation-widget'); ?></p>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('input[name="vpw_settings[visibility_type]"]').on('change', function() {
                $('#vpw_specific_pages, #vpw_specific_posts').hide();
                if ($(this).val() === 'specific_pages') {
                    $('#vpw_specific_pages').show();
                } else if ($(this).val() === 'specific_posts') {
                    $('#vpw_specific_posts').show();
                }
            });
        });
        </script>
        <?php
    }
    
    public function primary_color_field_callback() {
        $options = get_option('vpw_settings');
        $value = isset($options['primary_color']) ? esc_attr($options['primary_color']) : '#fdba74';
        echo '<input type="text" name="vpw_settings[primary_color]" value="' . $value . '" class="vpw-color-picker" />';
        echo '<p class="description">' . __('Choose the primary color for the widget', 'video-presentation-widget') . '</p>';
    }
    
    public function widget_size_field_callback() {
        $options = get_option('vpw_settings');
        $value = isset($options['widget_size']) ? esc_attr($options['widget_size']) : 100;
        echo '<input type="number" name="vpw_settings[widget_size]" value="' . $value . '" min="60" max="200" step="10" /> px';
        echo '<p class="description">' . __('Size of the widget trigger button (60-200px)', 'video-presentation-widget') . '</p>';
    }
    
    public function modal_dimensions_field_callback() {
        $options = get_option('vpw_settings');
        $width = isset($options['modal_width']) ? esc_attr($options['modal_width']) : 340;
        $height = isset($options['modal_height']) ? esc_attr($options['modal_height']) : 650;
        ?>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div>
                <label><?php _e('Width:', 'video-presentation-widget'); ?></label><br>
                <input type="number" name="vpw_settings[modal_width]" value="<?php echo $width; ?>" min="280" max="800" step="10" style="width: 100px;" /> px
            </div>
            <div>
                <label><?php _e('Height:', 'video-presentation-widget'); ?></label><br>
                <input type="number" name="vpw_settings[modal_height]" value="<?php echo $height; ?>" min="400" max="900" step="10" style="width: 100px;" /> px
            </div>
        </div>
        <p class="description"><?php _e('Customize the modal width (280-800px) and height (400-900px)', 'video-presentation-widget'); ?></p>
        <?php
    }
    
    public function position_field_callback() {
        $options = get_option('vpw_settings');
        $value = isset($options['position']) ? $options['position'] : 'bottom-right';
        ?>
        <select name="vpw_settings[position]">
            <option value="bottom-right" <?php selected($value, 'bottom-right'); ?>><?php _e('Bottom Right', 'video-presentation-widget'); ?></option>
            <option value="bottom-left" <?php selected($value, 'bottom-left'); ?>><?php _e('Bottom Left', 'video-presentation-widget'); ?></option>
        </select>
        <p class="description"><?php _e('Choose where the widget appears on the screen', 'video-presentation-widget'); ?></p>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'video-presentation-widget') === false) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_style(
            'vpw-admin-css',
            VPW_PLUGIN_URL . 'assets/admin.css',
            array(),
            VPW_VERSION
        );
        
        wp_enqueue_script(
            'vpw-admin-js',
            VPW_PLUGIN_URL . 'assets/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            VPW_VERSION,
            true
        );
        
        wp_localize_script('vpw-admin-js', 'vpwAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vpw_nonce')
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        $options = get_option('vpw_settings');
        
        if (!isset($options['enable']) || !$options['enable']) {
            return;
        }
        
        if (!$this->should_display_widget()) {
            return;
        }
        
        $videos = $this->get_videos();
        if (empty($videos)) {
            return;
        }
        
        wp_enqueue_style(
            'vpw-frontend-css',
            VPW_PLUGIN_URL . 'assets/frontend.css',
            array(),
            VPW_VERSION
        );
        
        wp_enqueue_script(
            'vpw-frontend-js',
            VPW_PLUGIN_URL . 'assets/frontend.js',
            array('jquery'),
            VPW_VERSION,
            true
        );
        
        wp_localize_script('vpw-frontend-js', 'vpwData', array(
            'primaryColor' => isset($options['primary_color']) ? $options['primary_color'] : '#fdba74',
            'modalWidth' => isset($options['modal_width']) ? $options['modal_width'] : 340,
            'modalHeight' => isset($options['modal_height']) ? $options['modal_height'] : 650
        ));
    }
    
    /**
     * Check if widget should be displayed
     */
    private function should_display_widget() {
        $options = get_option('vpw_settings');
        $visibility_type = isset($options['visibility_type']) ? $options['visibility_type'] : 'all';
        
        if ($visibility_type === 'all') {
            return true;
        }
        
        if ($visibility_type === 'specific_pages') {
            $visibility_pages = isset($options['visibility_pages']) ? $options['visibility_pages'] : array();
            return is_page($visibility_pages);
        }
        
        if ($visibility_type === 'specific_posts') {
            $visibility_posts = isset($options['visibility_posts']) ? $options['visibility_posts'] : array();
            return is_single($visibility_posts);
        }
        
        return false;
    }
    
    /**
     * Render widget on frontend
     */
    public function render_widget() {
        $options = get_option('vpw_settings');
        
        if (!isset($options['enable']) || !$options['enable']) {
            return;
        }
        
        if (!$this->should_display_widget()) {
            return;
        }
        
        $videos = $this->get_videos();
        if (empty($videos)) {
            return;
        }
        
        // Filter enabled videos and sort by order
        $videos = array_filter($videos, function($video) {
            return isset($video['enabled']) && $video['enabled'];
        });
        
        uasort($videos, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        if (empty($videos)) {
            return;
        }
        
        $primary_color = isset($options['primary_color']) ? esc_attr($options['primary_color']) : '#fdba74';
        $widget_size = isset($options['widget_size']) ? absint($options['widget_size']) : 100;
        $position = isset($options['position']) ? esc_attr($options['position']) : 'bottom-right';
        
        // Get first video as default
        $first_video = reset($videos);
        $cover_image = $first_video['cover_image'];
        $cta_text = $first_video['cta_text'];
        
        include VPW_PLUGIN_DIR . 'templates/widget.php';
    }
}

// Initialize plugin
new Video_Presentation_Widget();

// Activation hook
register_activation_hook(__FILE__, 'vpw_activate');
function vpw_activate() {
    $default_settings = array(
        'enable' => 1,
        'visibility_type' => 'all',
        'visibility_pages' => array(),
        'visibility_posts' => array(),
        'primary_color' => '#fdba74',
        'widget_size' => 100,
        'modal_width' => 340,
        'modal_height' => 650,
        'position' => 'bottom-right'
    );
    
    if (!get_option('vpw_settings')) {
        add_option('vpw_settings', $default_settings);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'vpw_deactivate');
function vpw_deactivate() {
    // Cleanup if needed
}
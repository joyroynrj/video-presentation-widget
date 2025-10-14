<?php
/**
 * Admin Videos Management Template
 * 
 * @package Video_Presentation_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap vpw-admin-wrap">
    <h1>
        <?php _e('Manage Videos', 'video-presentation-widget'); ?>
        <button type="button" class="page-title-action vpw-add-video-btn">
            <?php _e('Add New Video', 'video-presentation-widget'); ?>
        </button>
    </h1>
    
    <p class="description">
        <?php _e('Create and manage multiple videos. Users can switch between videos by clicking the button tabs.', 'video-presentation-widget'); ?>
    </p>
    
    <div class="vpw-videos-container">
        <?php if (empty($videos)): ?>
            <div class="vpw-empty-state">
                <div class="vpw-empty-icon">🎥</div>
                <h2><?php _e('No videos yet', 'video-presentation-widget'); ?></h2>
                <p><?php _e('Get started by adding your first video presentation.', 'video-presentation-widget'); ?></p>
                <button type="button" class="button button-primary button-hero vpw-add-video-btn">
                    <?php _e('Add Your First Video', 'video-presentation-widget'); ?>
                </button>
            </div>
        <?php else: ?>
            <div class="vpw-videos-list" id="vpw-videos-sortable">
                <?php foreach ($videos as $video_id => $video): ?>
                    <div class="vpw-video-card" data-video-id="<?php echo esc_attr($video_id); ?>">
                        <div class="vpw-video-card-header">
                            <div class="vpw-video-drag-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </div>
                            <div class="vpw-video-info">
                                <h3 class="vpw-video-title">
                                    <?php echo esc_html($video['title']); ?>
                                    <?php if (!$video['enabled']): ?>
                                        <span class="vpw-video-disabled-badge"><?php _e('Disabled', 'video-presentation-widget'); ?></span>
                                    <?php endif; ?>
                                </h3>
                                <p class="vpw-video-meta">
                                    <?php 
                                    $type_label = '';
                                    switch($video['video_type']) {
                                        case 'youtube':
                                            $type_label = __('YouTube', 'video-presentation-widget');
                                            break;
                                        case 'vimeo':
                                            $type_label = __('Vimeo', 'video-presentation-widget');
                                            break;
                                        default:
                                            $type_label = __('Uploaded Video', 'video-presentation-widget');
                                    }
                                    echo esc_html($type_label);
                                    ?>
                                    • <?php echo esc_html($video['cta_text']); ?>
                                </p>
                            </div>
                            <div class="vpw-video-actions">
                                <button type="button" class="button vpw-edit-video-btn" data-video-id="<?php echo esc_attr($video_id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Edit', 'video-presentation-widget'); ?>
                                </button>
                                <button type="button" class="button vpw-delete-video-btn" data-video-id="<?php echo esc_attr($video_id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        
                        <?php if (!empty($video['cover_image'])): ?>
                        <div class="vpw-video-card-preview">
                            <img src="<?php echo esc_url($video['cover_image']); ?>" alt="<?php echo esc_attr($video['title']); ?>">
                            <div class="vpw-video-overlay">
                                <button type="button" class="button vpw-video-button-preview" style="background: <?php echo esc_attr($video['button_bg_color']); ?>; color: <?php echo esc_attr($video['button_text_color']); ?>">
                                    <?php echo esc_html($video['button_text']); ?>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Video Edit/Add Modal -->
<div id="vpw-video-modal" class="vpw-modal" style="display: none;">
    <div class="vpw-modal-overlay"></div>
    <div class="vpw-modal-content">
        <div class="vpw-modal-header">
            <h2 id="vpw-modal-title"><?php _e('Add New Video', 'video-presentation-widget'); ?></h2>
            <button type="button" class="vpw-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        
        <form id="vpw-video-form">
            <input type="hidden" id="vpw-video-id" name="video_id" value="">
            
            <div class="vpw-modal-body">
                <!-- General Settings -->
                <div class="vpw-form-section">
                    <h3><?php _e('General Settings', 'video-presentation-widget'); ?></h3>
                    
                    <div class="vpw-form-row">
                        <label class="vpw-form-label">
                            <input type="checkbox" name="enabled" id="vpw-video-enabled" value="1" checked>
                            <?php _e('Enable this video', 'video-presentation-widget'); ?>
                        </label>
                    </div>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-video-title"><?php _e('Video Title', 'video-presentation-widget'); ?></label>
                        <input type="text" id="vpw-video-title" name="title" class="regular-text" required>
                        <p class="description"><?php _e('The title shown at the top of the video modal', 'video-presentation-widget'); ?></p>
                    </div>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-video-cta"><?php _e('Call-to-Action Text', 'video-presentation-widget'); ?></label>
                        <input type="text" id="vpw-video-cta" name="cta_text" class="regular-text" required>
                        <p class="description"><?php _e('The text shown on hover', 'video-presentation-widget'); ?></p>
                    </div>
                </div>
                
                <!-- Video Settings -->
                <div class="vpw-form-section">
                    <h3><?php _e('Video Settings', 'video-presentation-widget'); ?></h3>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-video-type"><?php _e('Video Type', 'video-presentation-widget'); ?></label>
                        <select id="vpw-video-type" name="video_type" class="regular-text">
                            <option value="upload"><?php _e('Upload Video', 'video-presentation-widget'); ?></option>
                            <option value="youtube"><?php _e('YouTube Video', 'video-presentation-widget'); ?></option>
                            <option value="vimeo"><?php _e('Vimeo Video', 'video-presentation-widget'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-video-url"><?php _e('Video URL/File', 'video-presentation-widget'); ?></label>
                        <div class="vpw-input-group">
                            <input type="text" id="vpw-video-url" name="video_url" class="regular-text" required>
                            <button type="button" class="button vpw-upload-media-btn" data-media-type="video">
                                <?php _e('Upload Video', 'video-presentation-widget'); ?>
                            </button>
                        </div>
                        <p class="description"><?php _e('Upload a video file or enter YouTube/Vimeo URL', 'video-presentation-widget'); ?></p>
                    </div>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-cover-image"><?php _e('Cover Image', 'video-presentation-widget'); ?></label>
                        <div class="vpw-input-group">
                            <input type="text" id="vpw-cover-image" name="cover_image" class="regular-text">
                            <button type="button" class="button vpw-upload-media-btn" data-media-type="image">
                                <?php _e('Upload Image', 'video-presentation-widget'); ?>
                            </button>
                        </div>
                        <p class="description"><?php _e('Cover image displayed before video plays', 'video-presentation-widget'); ?></p>
                        <div id="vpw-cover-preview" class="vpw-media-preview" style="display: none;">
                            <img src="" alt="Cover preview">
                        </div>
                    </div>
                </div>
                
                <!-- Button Settings -->
                <div class="vpw-form-section">
                    <h3><?php _e('Action Button Settings', 'video-presentation-widget'); ?></h3>
                    
                    <div class="vpw-form-row">
                        <label for="vpw-button-text"><?php _e('Button Text', 'video-presentation-widget'); ?></label>
                        <input type="text" id="vpw-button-text" name="button_text" class="regular-text" placeholder="Watch Video" required>
                    </div>
                    <div class="vpw-form-row vpw-color-grid">
                        <div class="vpw-color-field">
                            <label for="vpw-button-bg"><?php _e('Background Color', 'video-presentation-widget'); ?></label>
                            <input type="text" id="vpw-button-bg" name="button_bg_color" class="vpw-color-picker" value="#fdba74">
                        </div>
                        
                        <div class="vpw-color-field">
                            <label for="vpw-button-text-color"><?php _e('Text Color', 'video-presentation-widget'); ?></label>
                            <input type="text" id="vpw-button-text-color" name="button_text_color" class="vpw-color-picker" value="#1a1a1a">
                        </div>
                        
                        <div class="vpw-color-field">
                            <label for="vpw-button-hover-bg"><?php _e('Hover Background', 'video-presentation-widget'); ?></label>
                            <input type="text" id="vpw-button-hover-bg" name="button_hover_bg" class="vpw-color-picker" value="#fb923c">
                        </div>
                        
                        <div class="vpw-color-field">
                            <label for="vpw-button-hover-text"><?php _e('Hover Text', 'video-presentation-widget'); ?></label>
                            <input type="text" id="vpw-button-hover-text" name="button_hover_text" class="vpw-color-picker" value="#000000">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="vpw-modal-footer">
                <button type="button" class="button vpw-modal-close"><?php _e('Cancel', 'video-presentation-widget'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Video', 'video-presentation-widget'); ?></button>
            </div>
        </form>
    </div>
</div>
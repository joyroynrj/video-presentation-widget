<?php
/**
 * Video Presentation Widget Template v2.0
 * 
 * @package Video_Presentation_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

// Encode videos as JSON for JavaScript
$videos_json = json_encode(array_values($videos));
?>

<div class="vpw-widget vpw-<?php echo esc_attr($position); ?>" 
     data-videos='<?php echo esc_attr($videos_json); ?>'
     style="--vpw-primary-color: <?php echo esc_attr($primary_color); ?>;">
    
    <!-- Widget Trigger Button -->
    <button type="button" 
            class="vpw-trigger" 
            aria-label="<?php echo esc_attr($cta_text); ?>"
            style="width: <?php echo absint($widget_size); ?>px; height: <?php echo absint($widget_size); ?>px;">
        
        <?php if ($cover_image): ?>
            <img src="<?php echo esc_url($cover_image); ?>" 
                 alt="Video presentation" 
                 class="vpw-trigger-image"
                 id="vpw-trigger-cover">
        <?php else: ?>
            <!-- Default play icon -->
            <svg class="vpw-trigger-image" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="45" fill="<?php echo esc_attr($primary_color); ?>"/>
                <polygon points="35,25 35,75 75,50" fill="white"/>
            </svg>
        <?php endif; ?>
    </button>
    
    <!-- Call-to-Action Container -->
    <div class="vpw-cta-container" 
         tabindex="0" 
         role="button" 
         aria-label="<?php echo esc_attr($cta_text); ?>">
        <div class="vpw-cta-bg"></div>
        <span class="vpw-cta-label" id="vpw-cta-text"><?php echo esc_html($cta_text); ?></span>
        <svg class="vpw-cta-icon" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
            <polygon points="79.093,0 48.907,30.187 146.72,128 48.907,225.813 79.093,256 207.093,128"/>
        </svg>
    </div>
</div>
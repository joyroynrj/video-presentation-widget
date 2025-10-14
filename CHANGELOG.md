# Changelog

All notable changes to Video Presentation Widget will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-01-14

### 🎉 Major Release - Multi-Video Support

### Added
- **Multi-Video Management System**
  - Add unlimited videos through admin interface
  - Each video has independent settings
  - Drag & drop reordering of videos
  - Enable/disable individual videos
  
- **Button-Based Video Switching**
  - Each video has its own styled button
  - Click buttons to switch between videos in modal
  - Active button state with visual indicator
  - Optional button URLs (navigate instead of switching)
  - Individual button styling per video (colors, hover states)
  
- **GitHub Auto-Updates**
  - Automatic plugin updates from GitHub releases
  - Version checking and notification
  - One-click updates from WordPress admin
  - Support for both public and private repositories
  
- **Modern Admin Interface**
  - Professional video management dashboard
  - Video cards with preview images
  - Inline editing with modal forms
  - Color picker for button customization
  - Media upload integration
  
- **Enhanced Features**
  - Video title overlay on modal
  - Improved animation and transitions
  - Better mobile responsiveness
  - Active state indication for current video

### Changed
- Restructured plugin architecture for scalability
- Separated settings into Videos and General Settings pages
- Improved JavaScript performance with better video loading
- Enhanced CSS for button styling and transitions
- Updated admin UI with modern design patterns

### Removed
- Old three-button system (replaced with dynamic video buttons)
- Hardcoded button configurations

### Technical
- Added `class-vpw-github-updater.php` for GitHub integration
- Implemented AJAX handlers for video CRUD operations
- Added video data structure with JSON storage
- Improved database option handling
- Better code organization and documentation

---

## [1.1.0] - 2024-XX-XX

### Added
- Initial plugin release
- Single video support
- Three fixed CTA buttons
- Video upload or YouTube/Vimeo embed
- Cover image support
- Widget visibility controls
- Position settings (bottom-right/left)
- Color customization
- Modal dimensions settings

### Features
- Video presentation widget in corner
- Hover-to-show CTA text
- Professional modal with video controls
- Mobile responsive design
- Accessibility features (keyboard navigation, ARIA labels)

---

## Upgrade Guide

### From 1.1.0 to 2.0.0

**⚠️ Important**: Version 2.0 introduces a completely new video management system.

#### What You Need to Do:

1. **Backup your current plugin** before updating
2. **Take note of your current video settings** (you'll need to re-enter them)
3. **Update the plugin files**
4. **Go to Video Widget → Manage Videos**
5. **Add your videos** using the new system
6. **Configure each video's button** (text, colors, URL)
7. **Test the widget** on your frontend

#### What Stays the Same:

- Widget position settings
- Primary color
- Widget size
- Modal dimensions  
- Visibility settings

#### What Changes:

- Video management moves from Settings to Manage Videos page
- Instead of 3 fixed buttons, you now have 1 button per video
- Button behavior: switches video OR navigates to URL (your choice)
- Each video has its own independent settings

#### Database Changes:

- Old option: `vpw_settings` (still used for general settings)
- New option: `vpw_videos` (stores all video data)
- No automatic migration - you'll manually recreate videos

#### Migration Steps:

```php
// Optional: If you want to preserve your old video, 
// you can create a one-time migration by adding this to your theme's functions.php
// Run once, then remove it

add_action('admin_init', 'vpw_migrate_old_video', 1);
function vpw_migrate_old_video() {
    if (get_option('vpw_migration_done')) {
        return;
    }
    
    $old_settings = get_option('vpw_settings', array());
    
    if (!empty($old_settings['video_url'])) {
        $video_data = array(
            'video_' . time() => array(
                'id' => 'video_' . time(),
                'title' => $old_settings['title'] ?? 'Imported Video',
                'cta_text' => $old_settings['cta_text'] ?? 'Watch Video',
                'video_type' => $old_settings['video_type'] ?? 'upload',
                'video_url' => $old_settings['video_url'],
                'cover_image' => $old_settings['cover_image'] ?? '',
                'button_text' => $old_settings['button1_text'] ?? 'Watch Now',
                'button_url' => $old_settings['button1_url'] ?? '',
                'button_bg_color' => $old_settings['button1_bg_color'] ?? '#fdba74',
                'button_text_color' => $old_settings['button1_text_color'] ?? '#1a1a1a',
                'button_hover_bg' => $old_settings['button1_hover_bg'] ?? '#fb923c',
                'button_hover_text' => $old_settings['button1_hover_text'] ?? '#000000',
                'enabled' => 1,
                'order' => 0
            )
        );
        
        update_option('vpw_videos', $video_data);
        update_option('vpw_migration_done', true);
    }
}
```

---

## Future Roadmap

### Planned for 2.1.0
- [ ] Video analytics (view counts, completion rates)
- [ ] Video categories/tags
- [ ] Custom CSS per video
- [ ] Video scheduling (show/hide by date)
- [ ] A/B testing support

### Planned for 2.2.0
- [ ] Video playlists
- [ ] Auto-advance to next video
- [ ] Video captions/subtitles support
- [ ] Integration with popular form plugins
- [ ] Lead capture forms in video modal

### Planned for 3.0.0
- [ ] Video hosting integration (Wistia, Vimeo Business)
- [ ] Advanced analytics dashboard
- [ ] Conversion tracking
- [ ] Multiple widget instances per page
- [ ] Conditional display rules (user role, location, etc.)

---

## Version Numbering

We follow Semantic Versioning:

- **Major (X.0.0)**: Breaking changes, major new features
- **Minor (2.X.0)**: New features, backward compatible
- **Patch (2.0.X)**: Bug fixes, minor improvements

---

## Support & Bug Reports

- **Issues**: https://github.com/yourusername/video-presentation-widget/issues
- **Documentation**: See README.md
- **Updates**: Watch releases on GitHub

---

[2.0.0]: https://github.com/yourusername/video-presentation-widget/releases/tag/v2.0.0
[1.1.0]: https://github.com/yourusername/video-presentation-widget/releases/tag/v1.1.0
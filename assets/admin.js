/**
 * Video Presentation Widget - Admin JavaScript v2.0
 */
(function($) {
    'use strict';

    let currentVideoId = null;
    let videosData = {}; // Store all videos data

    $(document).ready(function() {
        
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('.vpw-color-picker').wpColorPicker();
        }

        // Load videos data from page
        loadVideosDataFromPage();

        // Initialize sortable
        if ($('#vpw-videos-sortable').length) {
            $('#vpw-videos-sortable').sortable({
                handle: '.vpw-video-drag-handle',
                placeholder: 'vpw-video-placeholder',
                update: function(event, ui) {
                    saveVideoOrder();
                }
            });
        }

        // Add video button
        $(document).on('click', '.vpw-add-video-btn', function() {
            openVideoModal();
        });

        // Edit video button
        $(document).on('click', '.vpw-edit-video-btn', function() {
            const videoId = $(this).data('video-id');
            openVideoModal(videoId);
        });

        // Delete video button
        $(document).on('click', '.vpw-delete-video-btn', function() {
            const videoId = $(this).data('video-id');
            if (confirm('Are you sure you want to delete this video?')) {
                deleteVideo(videoId);
            }
        });

        // Close modal
        $(document).on('click', '.vpw-modal-close, .vpw-modal-overlay', function() {
            closeVideoModal();
        });

        // Form submit
        $('#vpw-video-form').on('submit', function(e) {
            e.preventDefault();
            saveVideo();
        });

        // Media uploader
        $(document).on('click', '.vpw-upload-media-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const mediaType = button.data('media-type');
            const targetField = button.prev('input');

            const frame = wp.media({
                title: mediaType === 'video' ? 'Select Video' : 'Select Image',
                button: { text: 'Use this ' + mediaType },
                library: { type: mediaType === 'video' ? 'video' : 'image' },
                multiple: false
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                targetField.val(attachment.url).trigger('change');
                
                // Show preview for cover image
                if (mediaType === 'image') {
                    $('#vpw-cover-preview').show().find('img').attr('src', attachment.url);
                }
            });

            frame.open();
        });

        // Video type change
        $('#vpw-video-type').on('change', function() {
            const videoType = $(this).val();
            const $uploadBtn = $('.vpw-upload-media-btn[data-media-type="video"]');
            const $videoUrlField = $('#vpw-video-url');

            if (videoType === 'upload') {
                $uploadBtn.show();
                $videoUrlField.attr('placeholder', 'Upload a video file or enter URL');
            } else {
                $uploadBtn.hide();
                if (videoType === 'youtube') {
                    $videoUrlField.attr('placeholder', 'Enter YouTube URL');
                } else if (videoType === 'vimeo') {
                    $videoUrlField.attr('placeholder', 'Enter Vimeo URL');
                }
            }
        });

        // Cover image field change
        $('#vpw-cover-image').on('change', function() {
            const url = $(this).val();
            if (url) {
                $('#vpw-cover-preview').show().find('img').attr('src', url);
            } else {
                $('#vpw-cover-preview').hide();
            }
        });
    });

    /**
     * Load videos data from page (from PHP rendered data)
     */
    function loadVideosDataFromPage() {
        $('.vpw-video-card').each(function() {
            const $card = $(this);
            const videoId = $card.data('video-id');
            
            // Store basic info - will load full data via AJAX when editing
            videosData[videoId] = {
                id: videoId
            };
        });
    }

    /**
     * Open video modal
     */
    function openVideoModal(videoId = null) {
        currentVideoId = videoId;
        
        if (videoId) {
            // Edit mode - load video data
            $('#vpw-modal-title').text('Edit Video');
            loadVideoData(videoId);
        } else {
            // Add mode - reset form
            $('#vpw-modal-title').text('Add New Video');
            resetForm();
        }
        
        $('#vpw-video-modal').fadeIn(300);
        $('body').addClass('vpw-modal-open');
    }

    /**
     * Close video modal
     */
    function closeVideoModal() {
        $('#vpw-video-modal').fadeOut(300);
        $('body').removeClass('vpw-modal-open');
        currentVideoId = null;
    }

    /**
     * Reset form
     */
    function resetForm() {
        $('#vpw-video-form')[0].reset();
        $('#vpw-video-id').val('');
        $('#vpw-video-enabled').prop('checked', true);
        $('#vpw-cover-preview').hide();
        
        // Reset color pickers to default
        $('#vpw-button-bg').val('#fdba74');
        $('#vpw-button-text-color').val('#1a1a1a');
        $('#vpw-button-hover-bg').val('#fb923c');
        $('#vpw-button-hover-text').val('#000000');
        
        // Reinitialize color pickers after reset
        setTimeout(function() {
            if ($.fn.wpColorPicker) {
                $('.vpw-color-picker').wpColorPicker('color', $('#vpw-button-bg').val());
            }
        }, 100);
    }

    /**
     * Load video data via AJAX
     */
    function loadVideoData(videoId) {
        $.ajax({
            url: vpwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vpw_get_video',
                nonce: vpwAdmin.nonce,
                video_id: videoId
            },
            success: function(response) {
                if (response.success && response.data.video) {
                    const video = response.data.video;
                    
                    console.log('Loading video data:', video);
                    
                    $('#vpw-video-id').val(video.id || videoId);
                    $('#vpw-video-enabled').prop('checked', video.enabled == 1);
                    $('#vpw-video-title').val(video.title || '');
                    $('#vpw-video-cta').val(video.cta_text || '');
                    $('#vpw-video-type').val(video.video_type || 'upload').trigger('change');
                    $('#vpw-video-url').val(video.video_url || '');
                    $('#vpw-cover-image').val(video.cover_image || '').trigger('change');
                    $('#vpw-button-text').val(video.button_text || '');
                    $('#vpw-button-url').val(video.button_url || '');
                    
                    // Set color pickers
                    $('#vpw-button-bg').val(video.button_bg_color || '#fdba74');
                    $('#vpw-button-text-color').val(video.button_text_color || '#1a1a1a');
                    $('#vpw-button-hover-bg').val(video.button_hover_bg || '#fb923c');
                    $('#vpw-button-hover-text').val(video.button_hover_text || '#000000');
                    
                    // Reinitialize color pickers with loaded values
                    setTimeout(function() {
                        if ($.fn.wpColorPicker) {
                            $('.vpw-color-picker').each(function() {
                                const $input = $(this);
                                const color = $input.val();
                                $input.wpColorPicker('color', color);
                            });
                        }
                    }, 100);
                    
                } else {
                    showNotice('error', 'Failed to load video data');
                    console.error('Failed to load video:', response);
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'Error loading video data');
                console.error('AJAX error:', error);
            }
        });
    }

    /**
     * Save video
     */
    function saveVideo() {
        const formData = {
            action: 'vpw_save_video',
            nonce: vpwAdmin.nonce,
            video_id: $('#vpw-video-id').val(),
            enabled: $('#vpw-video-enabled').is(':checked') ? 1 : 0,
            title: $('#vpw-video-title').val(),
            cta_text: $('#vpw-video-cta').val(),
            video_type: $('#vpw-video-type').val(),
            video_url: $('#vpw-video-url').val(),
            cover_image: $('#vpw-cover-image').val(),
            button_text: $('#vpw-button-text').val(),
            button_url: $('#vpw-button-url').val(),
            button_bg_color: $('#vpw-button-bg').val(),
            button_text_color: $('#vpw-button-text-color').val(),
            button_hover_bg: $('#vpw-button-hover-bg').val(),
            button_hover_text: $('#vpw-button-hover-text').val()
        };

        // Show loading
        const $submitBtn = $('#vpw-video-form button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: vpwAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    closeVideoModal();
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    showNotice('error', response.data.message || 'Failed to save video');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while saving the video');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Delete video
     */
    function deleteVideo(videoId) {
        $.ajax({
            url: vpwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vpw_delete_video',
                nonce: vpwAdmin.nonce,
                video_id: videoId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    $('[data-video-id="' + videoId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        if ($('.vpw-video-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    showNotice('error', response.data.message || 'Failed to delete video');
                }
            }
        });
    }

    /**
     * Save video order
     */
    function saveVideoOrder() {
        const order = [];
        $('#vpw-videos-sortable .vpw-video-card').each(function() {
            order.push($(this).data('video-id'));
        });

        $.ajax({
            url: vpwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'vpw_reorder_videos',
                nonce: vpwAdmin.nonce,
                order: order
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Video order updated');
                }
            }
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.vpw-admin-wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
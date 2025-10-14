/**
 * Video Presentation Widget - Frontend JavaScript v2.1
 * Multi-video support with YouTube API integration
 */
(function($) {
    'use strict';

    class VideoWidget {
        constructor() {
            this.widget = $('.vpw-widget');
            this.trigger = $('.vpw-trigger');
            this.modal = null;
            this.isOpen = false;
            this.videos = [];
            this.currentVideoIndex = 0;
            this.videoElement = null;
            this.isPlaying = false;
            this.ytPlayer = null;
            this.isYouTube = false;
            this.actionsTimeout = null;
            this.progressInterval = null;
            
            this.init();
        }

        init() {
            if (!this.widget.length) return;

            // Load videos data
            this.videos = this.widget.data('videos');
            console.log('Loaded videos:', this.videos);
            
            if (!this.videos || this.videos.length === 0) {
                console.error('No videos found');
                return;
            }

            // Set primary color
            if (typeof vpwData !== 'undefined' && vpwData.primaryColor) {
                document.documentElement.style.setProperty('--vpw-primary-color', vpwData.primaryColor);
            }

            // Bind events
            this.bindEvents();

            // Create modal
            this.createModal();

            // Load YouTube API if needed
            if (this.videos.some(video => this.isYouTubeUrl(video.video_url))) {
                this.loadYouTubeAPI();
            }
        }

        isYouTubeUrl(url) {
            return url && (url.includes('youtube.com') || url.includes('youtu.be'));
        }

        getYouTubeVideoId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        loadYouTubeAPI() {
            if (window.YT && window.YT.Player) {
                console.log('YouTube API already loaded');
                return;
            }

            console.log('Loading YouTube API');
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

            window.onYouTubeIframeAPIReady = () => {
                console.log('YouTube API ready');
            };
        }

        bindEvents() {
            // Open modal on trigger click
            this.trigger.on('click', (e) => {
                e.preventDefault();
                this.openModal();
            });

            // Open modal on CTA click
            $('.vpw-cta-container').on('click', (e) => {
                e.preventDefault();
                this.openModal();
            });

            // Handle keyboard events
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.closeModal();
                }
            });
        }

        createModal() {
            const position = this.widget.hasClass('vpw-bottom-right') ? 'vpw-bottom-right' : 'vpw-bottom-left';
            const modalWidth = typeof vpwData !== 'undefined' && vpwData.modalWidth ? vpwData.modalWidth : 340;
            const modalHeight = typeof vpwData !== 'undefined' && vpwData.modalHeight ? vpwData.modalHeight : 650;

            // Create video switching buttons HTML (only if multiple videos)
            let videoButtonsHtml = '';
            if (this.videos.length > 1) {
                this.videos.forEach((video, index) => {
                    const activeClass = index === 0 ? 'vpw-active' : '';
                    const buttonText = video.button_text || 'Video ' + (index + 1);
                    videoButtonsHtml += `
                        <button class="vpw-video-btn ${activeClass}" data-index="${index}">
                            ${buttonText}
                        </button>
                    `;
                });
            }

            const modalHtml = `
                <div class="vpw-modal-overlay ${position}">
                    <div class="vpw-modal" role="dialog" aria-modal="true" style="width: ${modalWidth}px !important; height: ${modalHeight}px !important;">
                        <div class="vpw-modal-content">
                            <div class="vpw-video-container">
                                <!-- Video progress bar -->
                                <div class="vpw-video-progress">
                                    <div class="vpw-video-progress-bar"></div>
                                </div>
                                
                                <!-- Video Title Overlay (under progress bar) -->
                                <div class="vpw-video-title-overlay"></div>
                                
                                <!-- Top right controls -->
                                <div class="vpw-video-top-controls">
                                    <button class="vpw-control-btn vpw-close-btn" aria-label="Close" title="Close">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 6L6 18M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <button class="vpw-control-btn vpw-mute-btn-top" aria-label="Sound" title="Sound on/off">
                                        <svg class="vpw-sound-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 5L6 9H2v6h4l5 4V5z"/>
                                            <path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/>
                                        </svg>
                                        <svg class="vpw-sound-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                            <path d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6"/>
                                        </svg>
                                    </button>
                                    <button class="vpw-control-btn vpw-share-btn" aria-label="Share" title="Share video">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                                            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Video element -->
                                <div id="vpw-video-player"></div>
                                
                                <!-- Play button overlay -->
                                <div class="vpw-play-overlay">
                                    <svg viewBox="0 0 24 24" fill="white">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                                
                                <!-- Replay button -->
                                <div class="vpw-replay-overlay">
                                    <svg class="vpw-replay-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 4v6h6M23 20v-6h-6"/>
                                        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                                    </svg>
                                </div>
                                
                                <!-- Subtitle -->
                                <div class="vpw-video-subtitle"></div>
                                
                                <!-- Video switching buttons (only if multiple videos) -->
                                ${videoButtonsHtml ? `<div class="vpw-video-buttons">${videoButtonsHtml}</div>` : ''}
                                
                                <!-- Toggle button for actions -->
                                <button class="vpw-toggle-actions" aria-label="Show/hide buttons">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            this.modal = $('.vpw-modal-overlay');

            console.log('Modal created');

            // Apply button styles after modal is created
            this.applyButtonStyles();

            // Bind modal events
            this.bindModalEvents();
        }

        applyButtonStyles() {
            console.log('Applying button styles...');
            
            this.videos.forEach((video, index) => {
                const $btn = this.modal.find(`.vpw-video-btn[data-index="${index}"]`);
                
                if ($btn.length === 0) return;
                
                const normalBg = video.button_bg_color || '#fdba74';
                const normalText = video.button_text_color || '#1a1a1a';
                const hoverBg = video.button_hover_bg || '#fb923c';
                const hoverText = video.button_hover_text || '#000000';
                
                console.log(`Button ${index} colors:`, { normalBg, normalText, hoverBg, hoverText });
                
                // Apply initial styles
                $btn.attr('style', `
                    background: ${normalBg} !important;
                    background-color: ${normalBg} !important;
                    color: ${normalText} !important;
                    border-color: ${normalBg} !important;
                `);
                
                // Store hover colors as data attributes
                $btn.data('hover-bg', hoverBg);
                $btn.data('hover-text', hoverText);
                $btn.data('normal-bg', normalBg);
                $btn.data('normal-text', normalText);
                
                // Add hover events
                $btn.on('mouseenter', function() {
                    const $this = $(this);
                    $(this).attr('style', `
                        background: ${$this.data('hover-bg')} !important;
                        background-color: ${$this.data('hover-bg')} !important;
                        color: ${$this.data('hover-text')} !important;
                        border-color: ${$this.data('hover-bg')} !important;
                    `);
                }).on('mouseleave', function() {
                    const $this = $(this);
                    $(this).attr('style', `
                        background: ${$this.data('normal-bg')} !important;
                        background-color: ${$this.data('normal-bg')} !important;
                        color: ${$this.data('normal-text')} !important;
                        border-color: ${$this.data('normal-bg')} !important;
                    `);
                });
            });
        }

        bindModalEvents() {
            const self = this;

            // Close button
            this.modal.on('click', '.vpw-close-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.closeModal();
            });

            // Top mute button
            this.modal.on('click', '.vpw-mute-btn-top', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleMute();
            });

            // Share button
            this.modal.on('click', '.vpw-share-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.shareVideo();
            });

            // Center play overlay
            this.modal.on('click', '.vpw-play-overlay', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.playVideo();
            });

            // Replay overlay
            this.modal.on('click', '.vpw-replay-overlay', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.replayVideo();
            });

            // Toggle actions button
            this.modal.on('click', '.vpw-toggle-actions', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle actions clicked');
                self.toggleActions();
            });

            // Video switching button clicks
            this.modal.on('click', '.vpw-video-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const index = $(this).data('index');
                console.log('Switching to video index:', index);
                self.switchVideo(index);
            });

            // Click video to play/pause
            this.modal.on('click', '#vpw-video-player video', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.togglePlayPause();
            });

            // Click outside modal to close
            this.modal.on('click', function(e) {
                if ($(e.target).hasClass('vpw-modal-overlay')) {
                    self.closeModal();
                }
            });
        }

        setupVideoControls() {
            if (this.isYouTube) {
                this.setupYouTubeControls();
            } else {
                this.setupNativeVideoControls();
            }
        }

        setupNativeVideoControls() {
            if (!this.videoElement) {
                console.error('setupNativeVideoControls: No video element');
                return;
            }

            console.log('Setting up native video controls');

            $(this.videoElement).on('timeupdate', () => {
                const progress = (this.videoElement.currentTime / this.videoElement.duration) * 100;
                this.modal.find('.vpw-video-progress-bar').css('width', progress + '%');
            });

            $(this.videoElement).on('play', () => {
                console.log('Video play event fired');
                this.isPlaying = true;
                this.modal.find('.vpw-play-overlay').addClass('vpw-hidden');
                this.hideActionsAfterDelay();
                this.showSubtitle();
            });

            $(this.videoElement).on('pause', () => {
                console.log('Video paused');
                this.isPlaying = false;
                if (!this.videoElement.ended) {
                    this.modal.find('.vpw-play-overlay').removeClass('vpw-hidden');
                }
            });

            $(this.videoElement).on('ended', () => {
                console.log('Video ended');
                this.isPlaying = false;
                this.modal.find('.vpw-replay-overlay').addClass('vpw-show');
                this.showActions();
                this.modal.find('.vpw-toggle-actions').removeClass('vpw-show');
            });

            // Click on progress bar to seek
            this.modal.find('.vpw-video-progress').on('click', (e) => {
                const rect = e.currentTarget.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                this.videoElement.currentTime = percent * this.videoElement.duration;
            });
        }

        setupYouTubeControls() {
            console.log('Setting up YouTube controls');
            
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            this.progressInterval = setInterval(() => {
                if (this.ytPlayer && typeof this.ytPlayer.getCurrentTime === 'function' && typeof this.ytPlayer.getDuration === 'function') {
                    const currentTime = this.ytPlayer.getCurrentTime();
                    const duration = this.ytPlayer.getDuration();
                    if (duration > 0) {
                        const progress = (currentTime / duration) * 100;
                        this.modal.find('.vpw-video-progress-bar').css('width', progress + '%');
                    }
                }
            }, 100);
        }

        openModal() {
            console.log('Opening modal');
            this.modal.addClass('vpw-active');
            this.isOpen = true;

            // Hide play overlay initially for autoplay
            this.modal.find('.vpw-play-overlay').addClass('vpw-hidden');
            
            // Make sure buttons are visible
            this.showActions();
            
            // Make sure toggle button is visible from start
            this.modal.find('.vpw-toggle-actions').addClass('vpw-show vpw-expanded');

            // Load first video
            this.loadVideo(0);

            // Autoplay video after a brief delay
            setTimeout(() => {
                this.playVideo();
            }, 300);

            // Focus management for accessibility
            setTimeout(() => {
                this.modal.find('.vpw-close-btn').focus();
            }, 300);

            // Trap focus within modal
            this.trapFocus();
        }

        closeModal() {
            console.log('Closing modal');
            this.modal.removeClass('vpw-active');
            this.isOpen = false;

            // Clear progress interval
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }

            // Stop and reset video
            this.stopVideo();

            // Reset iframe for YouTube/Vimeo
            const iframe = this.modal.find('iframe');
            if (iframe.length) {
                const src = iframe.attr('src');
                if (src) {
                    iframe.attr('src', src.replace('autoplay=1', 'autoplay=0'));
                }
            }

            // Reset UI state
            this.isPlaying = false;
            this.modal.find('.vpw-play-overlay').removeClass('vpw-hidden');
            this.modal.find('.vpw-replay-overlay').removeClass('vpw-show');
            this.modal.find('.vpw-video-subtitle').removeClass('vpw-show');
            this.showActions();
            this.modal.find('.vpw-toggle-actions').removeClass('vpw-show vpw-expanded');
            this.modal.find('.vpw-video-progress-bar').css('width', '0%');

            // Return focus to trigger
            this.trigger.focus();
        }

        loadVideo(index) {
            if (index < 0 || index >= this.videos.length) return;

            console.log('Loading video:', index);
            this.currentVideoIndex = index;
            const video = this.videos[index];
            
            console.log('Video URL:', video.video_url);

            // Update active button
            this.modal.find('.vpw-video-btn').removeClass('vpw-active');
            this.modal.find(`.vpw-video-btn[data-index="${index}"]`).addClass('vpw-active');

            // Update video title overlay
            this.updateVideoTitle(video);

            // Check if YouTube
            this.isYouTube = this.isYouTubeUrl(video.video_url);
            console.log('Is YouTube:', this.isYouTube);

            // Clear previous video
            this.stopVideo();
            $('#vpw-video-player').empty();

            if (this.isYouTube) {
                this.loadYouTubeVideo(video);
            } else {
                this.loadNativeVideo(video);
            }

            // Update subtitle
            this.updateSubtitle(video);
        }

        loadYouTubeVideo(video) {
            const videoId = this.getYouTubeVideoId(video.video_url);
            if (!videoId) {
                console.error('Invalid YouTube URL');
                return;
            }

            console.log('Loading YouTube video:', videoId);

            $('#vpw-video-player').html('<div id="yt-player"></div>');

            const initPlayer = () => {
                this.ytPlayer = new YT.Player('yt-player', {
                    videoId: videoId,
                    playerVars: {
                        autoplay: 0,
                        controls: 0,
                        modestbranding: 1,
                        rel: 0,
                        showinfo: 0,
                        fs: 0,
                        playsinline: 1,
                        disablekb: 1
                    },
                    events: {
                        onReady: (event) => {
                            console.log('YouTube player ready');
                            this.setupYouTubeControls();
                        },
                        onStateChange: (event) => {
                            console.log('YouTube state:', event.data);
                            if (event.data === YT.PlayerState.PLAYING) {
                                this.isPlaying = true;
                                this.modal.find('.vpw-play-overlay').addClass('vpw-hidden');
                                this.hideActionsAfterDelay();
                                this.showSubtitle();
                            } else if (event.data === YT.PlayerState.PAUSED) {
                                this.isPlaying = false;
                                this.modal.find('.vpw-play-overlay').removeClass('vpw-hidden');
                            } else if (event.data === YT.PlayerState.ENDED) {
                                this.isPlaying = false;
                                this.modal.find('.vpw-replay-overlay').addClass('vpw-show');
                                this.showActions();
                                this.modal.find('.vpw-toggle-actions').removeClass('vpw-show');
                                if (this.progressInterval) {
                                    clearInterval(this.progressInterval);
                                }
                            }
                        }
                    }
                });
            };

            if (window.YT && window.YT.Player) {
                initPlayer();
            } else {
                window.onYouTubeIframeAPIReady = initPlayer;
            }
        }

        loadNativeVideo(video) {
            console.log('Loading native video:', video.video_url);

            const videoHtml = `
                <video 
                    id="vpw-video" 
                    playsinline 
                    webkit-playsinline
                    preload="metadata"
                >
                    <source src="${video.video_url}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            `;

            $('#vpw-video-player').html(videoHtml);
            this.videoElement = document.getElementById('vpw-video');
            
            this.setupVideoControls();
        }

        switchVideo(index) {
            console.log('Switching to video:', index);
            
            // Load new video
            this.loadVideo(index);
            
            // Autoplay the new video after a short delay
            setTimeout(() => {
                console.log('Autoplaying after switch');
                this.playVideo();
            }, 500);
        }

        togglePlayPause() {
            if (this.videoElement) {
                if (this.videoElement.paused) {
                    this.playVideo();
                } else {
                    this.pauseVideo();
                }
            }
        }

        pauseVideo() {
            if (this.videoElement) {
                this.videoElement.pause();
                this.isPlaying = false;
            }
        }

        playVideo() {
            console.log('Play video called');
            
            if (this.isYouTube) {
                if (this.ytPlayer && typeof this.ytPlayer.playVideo === 'function') {
                    console.log('Playing YouTube video');
                    this.ytPlayer.playVideo();
                    this.modal.find('.vpw-play-overlay').addClass('vpw-hidden');
                }
            } else {
                if (this.videoElement) {
                    console.log('Playing native video');
                    const playPromise = this.videoElement.play();
                    
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                console.log('Video playing successfully');
                                this.modal.find('.vpw-play-overlay').addClass('vpw-hidden');
                            })
                            .catch(error => {
                                console.log('Autoplay prevented:', error);
                                this.modal.find('.vpw-play-overlay').removeClass('vpw-hidden');
                            });
                    }
                }
            }
        }

        stopVideo() {
            if (this.isYouTube && this.ytPlayer) {
                if (typeof this.ytPlayer.stopVideo === 'function') {
                    this.ytPlayer.stopVideo();
                }
                if (typeof this.ytPlayer.destroy === 'function') {
                    this.ytPlayer.destroy();
                }
                this.ytPlayer = null;
            } else if (this.videoElement) {
                this.videoElement.pause();
                this.videoElement.currentTime = 0;
            }
            this.isPlaying = false;
        }

        replayVideo() {
            console.log('Replay video');
            this.modal.find('.vpw-replay-overlay').removeClass('vpw-show');
            
            if (this.isYouTube && this.ytPlayer) {
                if (typeof this.ytPlayer.seekTo === 'function') {
                    this.ytPlayer.seekTo(0);
                }
                if (typeof this.ytPlayer.playVideo === 'function') {
                    this.ytPlayer.playVideo();
                }
                this.setupYouTubeControls();
            } else if (this.videoElement) {
                this.videoElement.currentTime = 0;
                this.videoElement.play();
            }
        }

        toggleMute() {
            console.log('toggleMute called');
            
            if (this.isYouTube && this.ytPlayer) {
                if (typeof this.ytPlayer.isMuted === 'function' && typeof this.ytPlayer.mute === 'function' && typeof this.ytPlayer.unMute === 'function') {
                    if (this.ytPlayer.isMuted()) {
                        this.ytPlayer.unMute();
                        this.modal.find('.vpw-sound-off').hide();
                        this.modal.find('.vpw-sound-on').show();
                    } else {
                        this.ytPlayer.mute();
                        this.modal.find('.vpw-sound-on').hide();
                        this.modal.find('.vpw-sound-off').show();
                    }
                }
            } else if (this.videoElement) {
                this.videoElement.muted = !this.videoElement.muted;
                if (this.videoElement.muted) {
                    this.modal.find('.vpw-sound-on').hide();
                    this.modal.find('.vpw-sound-off').show();
                } else {
                    this.modal.find('.vpw-sound-on').show();
                    this.modal.find('.vpw-sound-off').hide();
                }
            }
        }

        shareVideo() {
            const video = this.videos[this.currentVideoIndex];
            const videoUrl = video.video_url;
            
            let shareUrl = videoUrl || window.location.href;
            
            console.log('Sharing video URL:', shareUrl);
            
            // Try native share API first (mobile)
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this video!',
                    url: shareUrl
                }).then(() => {
                    console.log('Share successful');
                }).catch((error) => {
                    console.log('Share cancelled or failed:', error);
                    this.copyToClipboard(shareUrl);
                });
            } else {
                // Desktop: Copy to clipboard
                this.copyToClipboard(shareUrl);
            }
        }

        copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Link copied!');
                });
            } else {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Link copied!');
            }
        }

        updateVideoTitle(video) {
            const title = video.title || '';
            this.modal.find('.vpw-video-title-overlay').text(title);
        }

        updateSubtitle(video) {
            const subtitle = video.cta_text || '';
            this.modal.find('.vpw-video-subtitle').html(subtitle);
        }

        showSubtitle() {
            this.modal.find('.vpw-video-subtitle').addClass('vpw-show');
            setTimeout(() => {
                this.modal.find('.vpw-video-subtitle').removeClass('vpw-show');
            }, 5000);
        }

        hideActionsAfterDelay() {
            if (this.actionsTimeout) {
                clearTimeout(this.actionsTimeout);
            }
            
            this.actionsTimeout = setTimeout(() => {
                if (this.isPlaying) {
                    console.log('Hiding actions after delay');
                    this.hideActions();
                    this.modal.find('.vpw-toggle-actions').addClass('vpw-show');
                }
            }, 3000);
        }

        hideActions() {
            console.log('hideActions called');
            const videoButtons = this.modal.find('.vpw-video-buttons');
            const toggleBtn = this.modal.find('.vpw-toggle-actions');
            
            videoButtons.addClass('vpw-hidden');
            toggleBtn.removeClass('vpw-expanded');
        }

        showActions() {
            console.log('showActions called');
            const videoButtons = this.modal.find('.vpw-video-buttons');
            const toggleBtn = this.modal.find('.vpw-toggle-actions');
            
            videoButtons.removeClass('vpw-hidden');
            toggleBtn.addClass('vpw-expanded');
        }

        toggleActions() {
            console.log('toggleActions called');
            const videoButtons = this.modal.find('.vpw-video-buttons');
            
            if (videoButtons.hasClass('vpw-hidden')) {
                this.showActions();
            } else {
                this.hideActions();
            }
        }

        trapFocus() {
            const focusableElements = this.modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstFocusable = focusableElements.first();
            const lastFocusable = focusableElements.last();

            this.modal.on('keydown', (e) => {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable[0]) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable[0]) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            });
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        new VideoWidget();
    });

})(jQuery);
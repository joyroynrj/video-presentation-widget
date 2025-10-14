<?php
/**
 * GitHub Updater Class
 * Handles automatic updates from GitHub repository
 * 
 * @package Video_Presentation_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class VPW_GitHub_Updater {
    
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    
    public function __construct($plugin_file, $github_username, $github_repo, $access_token = '') {
        $this->file = $plugin_file;
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
        $this->username = $github_username;
        $this->repository = $github_repo;
        $this->authorize_token = $access_token;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        // Clear cache action
        add_action('upgrader_process_complete', array($this, 'purge_cache'), 10, 2);
    }
    
    /**
     * Get information from GitHub API
     */
    private function get_repository_info() {
        if (is_null($this->github_response)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository);
            
            $args = array();
            if ($this->authorize_token) {
                $args['headers'] = array(
                    'Authorization' => "token {$this->authorize_token}"
                );
            }
            
            $response = wp_remote_get($request_uri, $args);
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code != 200) {
                return false;
            }
            
            $this->github_response = json_decode(wp_remote_retrieve_body($response));
        }
        
        return $this->github_response;
    }
    
    /**
     * Modify the plugin transient
     */
    public function modify_transient($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $repo_info = $this->get_repository_info();
        
        if ($repo_info === false) {
            return $transient;
        }
        
        $current_version = $this->plugin['Version'];
        $latest_version = ltrim($repo_info->tag_name, 'v');
        
        // Compare versions
        if (version_compare($current_version, $latest_version, '<')) {
            $plugin_data = array(
                'slug' => $this->basename,
                'new_version' => $latest_version,
                'url' => $this->plugin['PluginURI'],
                'package' => $repo_info->zipball_url,
                'tested' => '',
                'icons' => array()
            );
            
            $transient->response[$this->basename] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Show plugin information popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!empty($args->slug)) {
            if ($args->slug == current(explode('/', $this->basename))) {
                $repo_info = $this->get_repository_info();
                
                if ($repo_info === false) {
                    return $result;
                }
                
                $plugin = array(
                    'name' => $this->plugin['Name'],
                    'slug' => $this->basename,
                    'version' => ltrim($repo_info->tag_name, 'v'),
                    'author' => $this->plugin['AuthorName'],
                    'author_profile' => $this->plugin['AuthorURI'],
                    'last_updated' => $repo_info->published_at,
                    'homepage' => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections' => array(
                        'Description' => $this->plugin['Description'],
                        'Updates' => $repo_info->body,
                    ),
                    'download_link' => $repo_info->zipball_url
                );
                
                return (object) $plugin;
            }
        }
        
        return $result;
    }
    
    /**
     * After install callback
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        if ($this->active) {
            activate_plugin($this->basename);
        }
        
        return $result;
    }
    
    /**
     * Clear update cache
     */
    public function purge_cache($upgrader, $options) {
        if (
            $options['action'] == 'update' &&
            $options['type'] === 'plugin' &&
            !empty($options['plugins'])
        ) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin == $this->basename) {
                    delete_transient('update_plugins');
                    break;
                }
            }
        }
    }
}
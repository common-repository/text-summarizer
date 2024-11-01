<?php
/**
 * Plugin Name: Text Summarizer
 * Plugin URI:  https://textsummarizer.io/
 * Author: TopTech News
 *
 * Description: Text Summarizer is a plugin that allows you to summarize content using an API integration.
 * Version: 1.0.0
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: summarize-content
 * Domain Path: /languages
 * 
 * Copyright (C) 2023 TopTech News
 */

namespace TopTechNewsTextSummarizer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class TopTechNewsTextSumm_Plugin {
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      TopTechNewsTextSumm_Plugin|null    $instance    The single instance of this class.
     */
    protected static ?TopTechNewsTextSumm_Plugin $instance = null;
    //const TEXT_DOMAIN = 'summarize-content'; // Class constant for text domain
    protected $summaryHistoryData = [];

    /**
     * Provides a single instance of this class.
     *
     * @since    1.0.0
     * @return   TopTechNewsTextSumm_Plugin    A single instance of this class.
     */
    public static function getInstance(): TopTechNewsTextSumm_Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     */
    private function __construct() {
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        $this->defineAdminHooks();
        // Register AJAX action for both logged-in and non-logged-in users
        add_action('wp_ajax_TopTechNewsTextSumm_validate_and_update_api_token', [$this, 'TopTechNewsTextSumm_validateAndUpdateApiTokenCallback']);
        add_action('admin_init', [$this, 'TopTechNewsTextSumm_registerApiTokenSettings']); // Register API token settings
        add_action('wp_ajax_TopTechNewsTextSumm_summarize_content_action', [$this, 'TopTechNewsTextSumm_summarizeContentCallback']);
        add_action('wp_ajax_nopriv_TopTechNewsTextSumm_summarize_content_action', [$this, 'TopTechNewsTextSumm_summarizeContentCallback']);
        add_action('wp_ajax_TopTechNewsTextSumm_fetchTokenCount', [$this, 'TopTechNewsTextSumm_fetchTokenCount']);
        add_action('admin_enqueue_scripts', [$this, 'TopTechNewsTextSumm_enqueueCustomScriptsAndStyles']);
        add_action('add_meta_boxes', [$this, 'TopTechNewsTextSumm_addCustomMetaBox']);
        add_action('wp_ajax_TopTechNewsTextSumm_summarizePost', [$this, 'TopTechNewsTextSumm_handleSummarizePostRequest']);
        add_action('wp_ajax_nopriv_TopTechNewsTextSumm_summarizePost', [$this, 'TopTechNewsTextSumm_handleSummarizePostRequest']); // If applicable
        add_action('wp_ajax_TopTechNewsTextSumm_updatePostContent', [$this, 'TopTechNewsTextSumm_updatePostContent']);
    }

    public static function activate() {
        if (false === get_option('TopTechNewsTextSumm_remaining_limit')) {
            add_option('TopTechNewsTextSumm_remaining_limit', 0);
        }
        if (false === get_option('TopTechNewsTextSumm_plan_limit')) {
            add_option('TopTechNewsTextSumm_plan_limit', 0);
        }
    }
    
    public static function uninstall() {
        delete_option('TopTechNewsTextSumm_remaining_limit');
        delete_option('TopTechNewsTextSumm_plan_limit');
        delete_option('TopTechNewsTextSumm_api_token');
    }
    

    public function loadTextDomain() {
        load_plugin_textdomain('summarize-content', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Register all of the hooks related to the administrative functionality
     * of the plugin.
     */
    private function defineAdminHooks() {
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        // More hooks can be added here
    }

    public function addAdminMenus() {
        // Main menu
        add_menu_page(
            'Text Summarizer', // Page title
            'Text Summarizer', // Menu title
            'manage_options', // Capability
            'TopTechNewsTextSumm_summarize_content', // Menu slug
            [$this, 'TopTechNewsTextSumm_summarizeContentPage'], // Callback function
            'dashicons-admin-tools', // Icon URL
            6 // Position
        );

        // Sub-menu for API Token
        add_submenu_page(
            'TopTechNewsTextSumm_summarize_content', // Parent slug
            'API Token', // Page title
            'API Token', // Menu title
            'manage_options', // Capability
            'TopTechNewsTextSumm_api_token', // Menu slug
            [$this, 'TopTechNewsTextSumm_apiTokenCallback'] // Callback function
        );

        // Sub-menu for Summary History
        add_submenu_page(
            'TopTechNewsTextSumm_summarize_content', // Parent slug
            'Summary History', // Page title
            'Summary History', // Menu title
            'manage_options', // Capability
            'TopTechNewsTextSumm_summary_history', // Menu slug
            [$this, 'TopTechNewsTextSumm_historyCallback'] // Callback function
        );
    }

    // Callback for the main menu page content
    public function TopTechNewsTextSumm_summarizeContentPage() {
        include_once(plugin_dir_path(__FILE__) . 'summarize-content-page.php');
        $this->enqueuePageSpecificScripts();
    }

    private function enqueuePageSpecificScripts() {
        wp_enqueue_script('summarize-content-script', plugin_dir_url(__FILE__) . 'js/summarize-content-script.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'js/summarize-content-script.js'), true);
        wp_enqueue_style('main-styles', plugin_dir_url(__FILE__) . 'css/sc-style.css', [], filemtime(plugin_dir_path(__FILE__) . 'css/sc-style.css'), false);
        wp_enqueue_style('animate-css', plugin_dir_url(__FILE__) . 'css/animate.min.css', [], filemtime(plugin_dir_path(__FILE__) . 'css/animate.min.css'));
        wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__) . 'css/font-awesome.min.css', [], filemtime(plugin_dir_path(__FILE__) . 'css/font-awesome.min.css'));

        $token_count_nonce = wp_create_nonce('sc_token_count_nonce_action');
        wp_localize_script('summarize-content-script', 'tokenCountData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => $token_count_nonce,
        ]);
    }

    public function enqueueScripts() {
        if (!did_action('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        wp_enqueue_script('summarize_content_ajax', plugin_dir_url(__FILE__) . 'js/token_ajax.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'js/token_ajax.js'), true);
        wp_localize_script('summarize_content_ajax', 'summarize_content_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('api_token_nonce'),
        ]);
    }

    public function TopTechNewsTextSumm_validateAndUpdateApiTokenCallback() {
        // Check AJAX referer for security
        check_ajax_referer('api_token_nonce', 'nonce');

        // Ensure current user has the capability to manage options
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access.', 'summarize-content'));
            return;
        }

        $api_token = isset($_POST['api_token']) ? sanitize_text_field(wp_unslash($_POST['api_token'])) : '';

        if (!preg_match('/^\d+\|[a-zA-Z0-9]+$/', $api_token)) {
            wp_send_json_error(__('Invalid API token format.', 'summarize-content'));
            return;
        }

        $api_url = esc_url_raw('https://textsummarizer.io/api/update-api-token?token=' . urlencode($api_token));

        $response = wp_remote_get($api_url, ['timeout' => 15, 'httpversion' => '1.1']);

        if (is_wp_error($response)) {
            wp_send_json_error(esc_html($response->get_error_message()));
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            if (isset($response_data['success'])) {
                update_option('TopTechNewsTextSumm_api_token', sanitize_text_field($api_token));
                update_option('TopTechNewsTextSumm_plan_limit', intval($response_data['success']['plan_limit_ttn']));
                update_option('TopTechNewsTextSumm_remaining_limit', intval($response_data['success']['remaining_limit_ttn']));
                wp_send_json_success(['message' => __('API token updated successfully.', 'summarize-content')]);
            } else {
                $error_message = isset($response_data['error']['message']) ? sanitize_text_field($response_data['error']['message']) : __('An unknown error occurred.', 'summarize-content');
                wp_send_json_error(['message' => $error_message]);
            }
        }
    }

    function TopTechNewsTextSumm_enqueueSettingsStyle() {
        wp_enqueue_style('settings-style', plugin_dir_url(__FILE__) . 'css/sc-settings-style.css', [], filemtime(plugin_dir_path(__FILE__) . 'css/sc-settings-style.css'), false);
    }

    public function TopTechNewsTextSumm_apiTokenCallback() {
        include_once(plugin_dir_path(__FILE__) . 'settings.php');
        $this->TopTechNewsTextSumm_enqueueSettingsStyle();
    }

    public function TopTechNewsTextSumm_registerApiTokenSettings() {
        register_setting('summarize_content_options', 'TopTechNewsTextSumm_api_token');
    }

    /**
     * Calls the summary API and handles the response.
     *
     * @param string $api_key The API key for authorization.
     * @param string $content The content to be summarized.
     * @param int $sum_length The desired length of the summary.
     * @param string $sum_quality The desired quality of the summary.
     * @return array Response array containing the summary or an error message.
     */
    public function TopTechNewsTextSumm_callSummaryApi($api_key, $content, $sum_length, $sum_quality) {
        $api_key = sanitize_text_field($api_key);
        $content = sanitize_textarea_field($content);
        $sum_length = intval($sum_length);
        $sum_quality = sanitize_text_field($sum_quality);

        $url = "https://textsummarizer.io/api/summarize-content";
        $body = [
            'authToken' => $api_key,
            'content' => $content,
            'summaryLength' => $sum_length,
            'summaryType' => $sum_quality,
        ];

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 45,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return ['error' => esc_html($response->get_error_message())];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code == 200 && isset($response_body['success'])) {
            update_option('TopTechNewsTextSumm_plan_limit', intval($response_body["success"]["plan_limit"]));
            update_option('TopTechNewsTextSumm_remaining_limit', intval($response_body["success"]["plan_limit"]) - intval($response_body["success"]["total_tokens_used"]));

            return [
                'success' => [
                    'message' => esc_html__('Summary generated successfully', 'summarize-content'),
                    'data' => [
                        'summarized_text' => sanitize_text_field($response_body["success"]["data"]),
                        'token_used' => intval($response_body["success"]["token_used"]),
                        'remaining_limit' => get_option('TopTechNewsTextSumm_remaining_limit'),
                    ],
                ],
            ];
        } else {
            $error_message = isset($response_body['error']['message']) ? sanitize_text_field($response_body['error']['message']) : __('An error occurred during the API call', 'summarize-content');
            return ['error' => ['message' => $error_message]];
        }
    }

    public function TopTechNewsTextSumm_summarizeContentCallback() {
        if (!isset($_POST['summarize_content_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['summarize_content_nonce'])), 'summarize_content_nc')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'summarize-content')]);
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized action.', 'summarize-content')]);
            return;
        }

        $content = isset($_POST['textarea']) ? sanitize_textarea_field(wp_unslash($_POST['textarea'])) : '';
        if (empty($content)) {
            wp_send_json_error(['message' => __('No text to summarize.', 'summarize-content')]);
            return;
        }

        $sum_length = isset($_POST['sum_length']) ? intval($_POST['sum_length']) : 100;
        $sum_quality = isset($_POST['sum_quality']) ? sanitize_text_field(wp_unslash($_POST['sum_quality'])) : 'default';

        $api_key = get_option('TopTechNewsTextSumm_api_token', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('Invalid API Key.', 'summarize-content')]);
            return;
        }

        $response = $this->TopTechNewsTextSumm_callSummaryApi($api_key, $content, $sum_length, $sum_quality);
        if (isset($response['error'])) {
            wp_send_json_error($response['error']);
        } else {
            wp_send_json_success($response['success']);
        }
    }

    public function TopTechNewsTextSumm_historyCallback() {

        wp_enqueue_style(
            'main-styles',
            plugin_dir_url(__FILE__) . 'css/sc-style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'css/sc-style.css'),
            false
        );
        wp_enqueue_style(
            'history-styles',
            plugin_dir_url(__FILE__) . 'css/history-style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'css/history-style.css'),
            false
        );

        $api_key = get_option('TopTechNewsTextSumm_api_token');
        if (!$api_key) {
            wp_die(__('Invalid API Key.', 'summarize-content'));
        }

        $url = "https://textsummarizer.io/api/summary-history";
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . sanitize_text_field($api_key),
            ],
            'body' => wp_json_encode(['authToken' => sanitize_text_field($api_key)]),
            'data_format' => 'body',
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            wp_die( 
                sprintf( 
                    esc_html__( 'Error retrieving history: %s', 'summarize-content' ), 
                    esc_html( $response->get_error_message() ) 
                ) 
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 200) {
            wp_die( 
                sprintf( 
                    esc_html__( 'Received error code from API: %s', 'summarize-content' ), 
                    esc_html( $response_code ) 
                ) 
            );
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (empty($decoded) || !$decoded['success']) {
            printf( 
                esc_html__( 'No data found for this API token (%s) or the response format is incorrect.', 'summarize-content' ),
                esc_html( $apiToken ) // Escaping the dynamic part for security
            );
            wp_die();
        }

        $this->summaryHistoryData = $decoded['data'];

        $template = plugin_dir_path(__FILE__) . 'summary-history-template.php';
        if (file_exists($template)) {
            include($template);
        } else {
            printf(
                esc_html__('Template not found: %s', 'summarize-content'),
                esc_html($template)
            );
        }
    }

    /**
     * Calculates the token count for the given content by making an API request.
     *
     * @param string $content The text content for which to calculate the token count.
     * @return array|WP_Error Associative array containing the number of tokens or WP_Error on failure.
     */
    public function TopTechNewsTextSumm_calculateTokenCount($content) {
        $sanitized_content = sanitize_text_field($content);
        
        $args = [
            'body' => wp_json_encode(['text' => $sanitized_content]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 45,
        ];

        $response = wp_remote_post('https://textsummarizer.io/api/check_prompt', $args);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return new WP_Error('request_failed', __('The request to the API endpoint failed or returned an invalid response.', 'summarize-content'));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['num_tokens'])) {
            return new WP_Error('invalid_response', __('The API response did not include the expected data.', 'summarize-content'));
        }

        return ['num_tokens' => intval($data['num_tokens'])];
    }

    /**
     * AJAX handler for fetching the token count of text content.
     *
     * Receives text content via POST, calculates the token count by making an API request,
     * and sends a JSON response back to the client. It performs nonce verification for
     * security and sanitizes the input text.
     */
    function TopTechNewsTextSumm_fetchTokenCount() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sc_token_count_nonce_action')) {
            wp_send_json_error(['message' => __('Nonce verification failed.', 'summarize-content')]);
            return;
        }

        $content = isset($_POST['text']) ? sanitize_textarea_field(wp_unslash($_POST['text'])) : '';
        if (empty($content)) {
            wp_send_json_error(['message' => __('No text provided.', 'summarize-content')]);
            return;
        }

        // Assuming sc_calculate_token_count is also refactored to match the new naming convention
        $output = $this->TopTechNewsTextSumm_calculateTokenCount($content);
        
        if (is_wp_error($output)) {
            wp_send_json_error(['message' => esc_html($output->get_error_message())]);
        } else {
            wp_send_json_success($output);
        }
    }

    public function TopTechNewsTextSumm_enqueueCustomScriptsAndStyles($hook) {
        if ('post.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'TopTechNewsTextSumm_summarizer_css',
            plugins_url('css/summarizer.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'css/summarizer.css')
        );

        wp_enqueue_script(
            'TopTechNewsTextSumm_summarizer_js',
            plugins_url('js/summarizer.js', __FILE__),
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'js/summarizer.js'),
            true
        );

        wp_localize_script(
            'TopTechNewsTextSumm_summarizer_js',
            'scsummarizerVars',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sc_update_post_content_nonce')
            ]
        );

        wp_localize_script(
            'TopTechNewsTextSumm_summarizer_js',
            'sc_summaraizer_updt_cntnt',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sc_summary_post_content_nonce')
            ]
        );
    }

    public function TopTechNewsTextSumm_addCustomMetaBox() {
        $screens = ['post'];
        foreach ($screens as $screen) {
            add_meta_box(
                'TopTechNewsTextSumm_summarizer_meta_box_id', // Unique ID for the meta box
                __('Summarize Content', 'summarize-content'), // Title of the meta box
                [$this, 'TopTechNewsTextSumm_renderMetaBoxContent'], // Callback method for rendering the meta box's HTML
                $screen,
                'side',
                'high'
            );
        }
    }

    public function TopTechNewsTextSumm_renderMetaBoxContent($post) {
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        ?>
        <form class="sc-summaraizer-form">
            <div class="sc-summaraizer-field">
                <label for="sc-summary-style"><?php esc_html_e('Summary Style:', 'summarize-content'); ?></label>
                <div class="sc-tooltip">
                    <i class="fa fa-info-circle"></i>
                    <span class="sc-tooltiptext">
                        <?php esc_html_e('Select the style for the summary display. Options include \'Precise\' for high accuracy, \'Balanced\' for readability and accuracy, and \'Creative\' for a creative twist. Choose the style that best fits your content and audience\'s needs.', 'summarize-content'); ?>
                    </span>
                </div>
                <select name="summary-style" id="sc-epSumStyl" class="sc-summaraizer-select">
                    <option value="precise"><?php esc_html_e('Precise', 'summarize-content'); ?></option>
                    <option value="balanced"><?php esc_html_e('Balanced', 'summarize-content'); ?></option>
                    <option value="creative"><?php esc_html_e('Creative', 'summarize-content'); ?></option>
                </select>
            </div>
            <div class="sc-summaraizer-field">
                <label for="sc-summary-length"><?php esc_html_e('Summary Length:', 'summarize-content'); ?></label>
                <div class="sc-tooltip">
                    <i class="fa fa-info-circle"></i>
                    <span class="sc-tooltiptext">
                        <?php esc_html_e('Select the desired length for your summary. A shorter length will provide a more condensed summary, while a longer length will provide a more detailed summary.', 'summarize-content'); ?>
                    </span>
                </div>
                <input type="range" name="summary-length" id="sc-epSumLen" min="50" max="200" class="sc-summaraizer-range">
                <span id="sc-summary-length-value">50</span>
            </div>
            <input type='hidden' id='sc-epPostId' value='<?php echo esc_attr($post->ID); ?>'>
            <div id="sc-ajax-msg" class="hide"></div>
            <textarea id="sc-summaryResult" class="sc-summaraizer-textarea" readonly hidden></textarea>
            <button type="button" id="sc-addSummaryBtn" class="sc-btn" hidden><?php esc_html_e('Add Summary to Content', 'summarize-content'); ?></button>
            <button type="button" id="sc-epSumBtn" class="sc-summaraizer-button">
                <i id="sc-spinner" class="fa fa-spinner fa-spin hide"></i><?php esc_html_e('Generate Summary', 'summarize-content'); ?>
            </button>
        </form>
        <?php
    }

    public function TopTechNewsTextSumm_handleSummarizePostRequest() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sc_update_post_content_nonce')) {
            wp_send_json_error(['message' => __('Nonce verification failed.', 'summarize-content')]);
            return;
        }
        
        $api_key = get_option('TopTechNewsTextSumm_api_token');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('Invalid API Key.', 'summarize-content')]);
            return;
        }
        
        $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
        if (!$post = get_post($postId)) {
            wp_send_json_error(__('Post not found.', 'summarize-content'));
            return;
        }

        $content = wp_strip_all_tags($post->post_content);
        if (empty($content)) {
            wp_send_json_error(__('Post content is empty.', 'summarize-content'));
            return;
        }
        
        $check_prompt_count = $this->TopTechNewsTextSumm_calculateTokenCount($content);
        if (is_wp_error($check_prompt_count)) {
            wp_send_json_error(esc_html($check_prompt_count->get_error_message()));
            return;
        }
        
        $prompt_tokens = $check_prompt_count['num_tokens'];
        $remaining_limit = get_option('TopTechNewsTextSumm_remaining_limit', 0);
        $summaryLength = isset($_POST['summaryLength']) ? intval($_POST['summaryLength']) : 0; 
        $summaryStyle = isset($_POST['summaryStyle']) ? sanitize_text_field($_POST['summaryStyle']) : '';
        if (($summaryLength * 3) + $prompt_tokens > $remaining_limit) {
            wp_send_json_error(__('Please increase your remaining limit, subscribe again, or add tokens.', 'summarize-content'));
            return;
        }

        $response = $this->TopTechNewsTextSumm_callSummaryApi($api_key, $content, $summaryLength, $summaryStyle); 
        if (isset($response['error'])) {
            wp_send_json_error($response['error']);
        } else {
            wp_send_json_success($response);
        }
    }

    public function TopTechNewsTextSumm_updatePostContent() {
        check_ajax_referer('sc_summary_post_content_nonce', 'nonce');

        $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
        if (!$postId || !current_user_can('edit_post', $postId)) {
            wp_send_json_error(__('Invalid post ID or insufficient permissions.', 'summarize-content'));
            return;
        }

        $summaryText = isset($_POST['summaryText']) ? sanitize_textarea_field($_POST['summaryText']) : '';
        if (empty($summaryText)) {
            wp_send_json_error(__('Summary text is empty.', 'summarize-content'));
            return;
        }

        $postContent = get_post_field('post_content', $postId, 'raw');
        if ($postContent === '') {
            wp_send_json_error(__('Post content is empty.', 'summarize-content'));
            return;
        }

        $summaryHTML = '<div class="notice notice-info"><h2 class="notice-title">' . esc_html__('In a Nutshell', 'summarize-content') . '</h2><p class="notice-summary-content"><i>' . esc_html($summaryText) . '</i></p></div>';

        if (strpos($postContent, $summaryHTML) === false) {
            $updatedContent = $summaryHTML . $postContent;
            $result = wp_update_post([
                'ID'           => $postId,
                'post_content' => $updatedContent,
            ], true);

            if (is_wp_error($result)) {
                wp_send_json_error(__('Error updating post content: ', 'summarize-content') . $result->get_error_message());
                return;
            }

            wp_send_json_success(__('Post content updated successfully.', 'summarize-content'));
        } else {
            wp_send_json_error(__('Summary already exists in post content.', 'summarize-content'));
        }
    }


}

// Register hooks for activation and uninstallation
register_activation_hook(__FILE__, ['TopTechNewsTextSummarizer\TopTechNewsTextSumm_Plugin', 'activate']);
register_uninstall_hook(__FILE__, ['TopTechNewsTextSummarizer\TopTechNewsTextSumm_Plugin', 'uninstall']);

TopTechNewsTextSumm_Plugin::getInstance();

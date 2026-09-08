<?php
/**
 * Plugin Name: AI Persian Chatbot
 * Plugin URI:  https://yourdomain.com/
 * Description: دستیار هوشمند پشتیبانی و راهنمای فروش ووکامرس
 * Version:     1.0.0
 * Author:      Developer Name
 * Text Domain: ai-persian-chatbot
 */

if (!defined('ABSPATH')) exit;

define('AIPC_PATH', plugin_dir_path(__FILE__));
define('AIPC_URL', plugin_dir_url(__FILE__));

require_once AIPC_PATH . 'admin/settings.php';
require_once AIPC_PATH . 'includes/class-groq-api.php';

add_action('wp_enqueue_scripts', 'aipc_register_assets');
function aipc_register_assets() {
    wp_enqueue_style('aipc-style', AIPC_URL . 'assets/css/style.css', array(), '1.0.0');
    wp_enqueue_script('aipc-script', AIPC_URL . 'assets/js/chat.js', array('jquery'), '1.0.0', true);

    wp_localize_script('aipc-script', 'aipc_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('aipc_chat_nonce')
    ));
}

add_action('wp_footer', 'aipc_render_chat_widget');
function aipc_render_chat_widget() {
    ?>
    <div id="aipc-widget-container">
        <button id="aipc-toggle-btn" aria-label="پشتیبانی آنلاین">
            <span class="aipc-icon-chat">💬</span>
            <span class="aipc-icon-close" style="display:none;">✕</span>
        </button>
        <div id="aipc-chat-window" class="aipc-hidden">
            <div class="aipc-header">
                <div class="aipc-avatar"></div>
                <div class="aipc-title">
                    <strong>پشتیبانی آنلاین</strong>
                    <span class="aipc-status">پاسخگوی سوالات شما</span>
                </div>
            </div>
            <div id="aipc-messages-body"></div>
            <div class="aipc-footer">
                <input type="text" id="aipc-input-field" placeholder="سوال خود را بنویسید..." autocomplete="off">
                <button id="aipc-send-btn" type="button">ارسال</button>
            </div>
        </div>
    </div>
    <?php
}

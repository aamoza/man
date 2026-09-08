<?php
/**
 * Plugin Name: GroShop - چت‌بات هوشمند ووکامرس
 * Description: چت‌بات و پشتیبان هوشمند فروشگاه بر پایه Groq API با پاسخ‌دهی فوق‌سریع زیر یک ثانیه.
 * Version:     1.0.0
 * Author:      تیم توسعه گروشاپ
 * Text Domain: groshop
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GROSHOP_VERSION', '1.0.0');
define('GROSHOP_PATH', plugin_dir_path(__FILE__));
define('GROSHOP_URL', plugin_dir_url(__FILE__));

// فراخوانی کلاس‌های اصلی بک‌اند
require_once GROSHOP_PATH . 'includes/class-groq-api.php';
require_once GROSHOP_PATH . 'includes/admin/class-admin-settings.php';

/**
 * بارگذاری فایل‌های CSS و JS در سمت کاربر (Front-end)
 */
add_action('wp_enqueue_scripts', 'groshop_enqueue_front_assets');
function groshop_enqueue_front_assets() {
    wp_enqueue_style('groshop-style', GROSHOP_URL . 'assets/css/style.css', array(), GROSHOP_VERSION);
    wp_enqueue_script('groshop-script', GROSHOP_URL . 'assets/js/chat.js', array('jquery'), GROSHOP_VERSION, true);

    // اعمال رنگ سفارشی تنظیم‌شده در پیشخوان
    $main_color = get_option('groshop_main_color', '#2563eb');
    wp_add_inline_style('groshop-style', ":root { --groshop-color: {$main_color}; }");

    // ارسال متغیرهای لازم به جاوااسکریپت
    wp_localize_script('groshop-script', 'groshop_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('groshop_chat_nonce')
    ));
}

/**
 * بارگذاری فایل‌های استایل پنل مدیریت (Admin)
 */
add_action('admin_enqueue_scripts', 'groshop_enqueue_admin_assets');
function groshop_enqueue_admin_assets($hook) {
    if (strpos($hook, 'groshop-settings') !== false) {
        wp_enqueue_style('groshop-admin-style', GROSHOP_URL . 'assets/css/admin.css', array(), GROSHOP_VERSION);
    }
}

/**
 * رندر کردن ویجت چت در فوتر صفحات
 */
add_action('wp_footer', 'groshop_render_chat_widget');
function groshop_render_chat_widget() {
    $api_status = get_option('groshop_api_status', '');
    
    // اگر API تنظیم یا فعال نشده باشد، ویجت نمایش داده نمی‌شود
    if ($api_status !== 'connected') {
        return;
    }

    $logo_url = GROSHOP_URL . 'assets/images/logo.png';
    ?>
    <div id="groshop-widget">
        <button id="groshop-toggle-btn" aria-label="پشتیبانی آنلاین">
            <img src="<?php echo esc_url($logo_url); ?>" alt="GroShop Logo" class="groshop-btn-logo" />
        </button>
        <div id="groshop-chat-window" class="groshop-hidden">
            <div class="groshop-header">
                <div class="groshop-header-brand">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="GroShop" class="groshop-header-logo" />
                    <div class="groshop-title">
                        <strong>پشتیبان هوشمند گروشاپ</strong>
                        <span class="groshop-status">آنلاین</span>
                    </div>
                </div>
                <button id="groshop-close-btn">&times;</button>
            </div>
            <div id="groshop-messages-body">
                <div class="groshop-msg groshop-msg-bot">سلام! چطور می‌تونم برای خرید محصولات راهنماییتون کنم؟</div>
            </div>
            <div class="groshop-footer">
                <input type="text" id="groshop-input" placeholder="سوال خود را بنویسید..." />
                <button id="groshop-send-btn">ارسال</button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * پردازش درخواست‌های AJAX ارسالی از چت‌بات
 */
add_action('wp_ajax_groshop_send_message', 'groshop_handle_ajax_message');
add_action('wp_ajax_nopriv_groshop_send_message', 'groshop_handle_ajax_message');

function groshop_handle_ajax_message() {
    check_ajax_referer('groshop_chat_nonce', 'nonce');

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

    if (empty($message)) {
        wp_send_json_error('پیام نمی‌تواند خالی باشد.');
    }

    $groq_api = new GroShop_Groq_API();
    $response = $groq_api->process_message($message);

    wp_send_json_success($response);
}

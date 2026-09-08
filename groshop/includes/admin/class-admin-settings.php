<?php
if (!defined('ABSPATH')) {
    exit;
}

class GroShop_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        $icon_url = GROSHOP_URL . 'assets/images/logo.png';

        add_menu_page(
            'تنظیمات گروشاپ',
            'گروشاپ (GroShop)',
            'manage_options',
            'groshop-settings',
            array($this, 'render_settings_page'),
            $icon_url,
            25
        );
    }

    public function register_settings() {
        register_setting('groshop_opt_group', 'groshop_api_key', array(
            'sanitize_callback' => array($this, 'validate_api_key')
        ));
        register_setting('groshop_opt_group', 'groshop_model', 'sanitize_text_field');
        register_setting('groshop_opt_group', 'groshop_system_prompt', 'sanitize_textarea_field');
        register_setting('groshop_opt_group', 'groshop_main_color', 'sanitize_hex_color');
    }

    public function validate_api_key($input_key) {
        $input_key = sanitize_text_field($input_key);

        if (empty($input_key)) {
            delete_option('groshop_api_status');
            return '';
        }

        $selected_model = get_option('groshop_model', 'llama-3.1-8b-instant');

        // بررسی صحت کلید API با ارسال درخواست تست سبک
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $input_key
            ),
            'body' => json_encode(array(
                'model'      => $selected_model,
                'messages'   => array(array('role' => 'user', 'content' => 'ping')),
                'max_tokens' => 1
            )),
            'timeout' => 10
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            update_option('groshop_api_status', 'connected');
        } else {
            update_option('groshop_api_status', 'error');
        }

        return $input_key;
    }

    public function render_settings_page() {
        $active_tab     = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $api_key        = get_option('groshop_api_key', '');
        $api_status     = get_option('groshop_api_status', '');
        $selected_model = get_option('groshop_model', 'llama-3.1-8b-instant');
        ?>
        <div class="wrap groshop-admin-wrap">
            <h1>تنظیمات چت‌بات هوشمند گروشاپ (GroShop)</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=groshop-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">تنظیمات عمومی & API</a>
                <a href="?page=groshop-settings&tab=appearance" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">شخصی‌سازی ظاهر</a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('groshop_opt_group');

                if ($active_tab === 'general') : ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">کلید Groq API</th>
                            <td>
                                <input type="password" name="groshop_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />

                                <?php if ($api_status === 'connected') : ?>
                                    <span class="groshop-badge groshop-badge-success">✔ اتصال فعال است</span>
                                <?php elseif ($api_status === 'error') : ?>
                                    <span class="groshop-badge groshop-badge-error">✖ کلید نامعتبر است</span>
                                <?php endif; ?>

                                <p class="description">
                                    کلید اختصاصی API را وارد کنید. اگر کلید ندارید، می‌توانید به‌صورت رایگان از طریق 
                                    <a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer">دریافت کلید رایگان API از Groq Console</a> 
                                    اقدام کنید.
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">انتخاب مدل هوش مصنوعی</th>
                            <td>
                                <select name="groshop_model" class="regular-text">
                                    <option value="llama-3.1-8b-instant" <?php selected($selected_model, 'llama-3.1-8b-instant'); ?>>LLaMA 3.1 8B Instant (پیشنهادی - بسیار سریع)</option>
                                    <option value="llama-3.3-70b-versatile" <?php selected($selected_model, 'llama-3.3-70b-versatile'); ?>>LLaMA 3.3 70B Versatile (پاسخ‌های دقیق‌تر و استدلال قوی‌تر)</option>
                                    <option value="mixtral-8x7b-32768" <?php selected($selected_model, 'mixtral-8x7b-32768'); ?>>Mixtral 8x7b (پردازش متن‌های بسیار طولانی)</option>
                                </select>
                                <p class="description">
                                    <strong>راهنمای انتخاب مدل:</strong> مدل <code>LLaMA 3.1 8B Instant</code> به دلیل پاسخ‌دهی لحظه‌ای (زیر ۱ ثانیه) و دقت عالی، بهترین گزینه برای چت‌بات پشتیبانی فروشگاه است.
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">دستور سیستمی (System Prompt)</th>
                            <td>
                                <textarea name="groshop_system_prompt" rows="5" class="large-text"><?php echo esc_textarea(get_option('groshop_system_prompt', 'تو یک پشتیبان صمیمی و حرفه‌ای برای فروشگاه هستی.')); ?></textarea>
                                <p class="description">نقش و نحوه پاسخ‌دهی هوش مصنوعی را به کاربر تعیین کنید.</p>
                            </td>
                        </tr>
                    </table>
                <?php else : ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">رنگ اصلی ویجت</th>
                            <td>
                                <input type="color" name="groshop_main_color" value="<?php echo esc_attr(get_option('groshop_main_color', '#2563eb')); ?>" />
                            </td>
                        </tr>
                    </table>
                <?php endif;

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new GroShop_Admin_Settings();

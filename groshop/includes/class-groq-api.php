<?php
if (!defined('ABSPATH')) {
    exit;
}

class GroShop_Groq_API {

    private $api_key;
    private $model;
    private $system_prompt;
    private $api_url = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct() {
        $this->api_key       = get_option('groshop_api_key', '');
        $this->model         = get_option('groshop_model', 'llama-3.1-8b-instant');
        $this->system_prompt = get_option('groshop_system_prompt', 'تو یک پشتیبان صمیمی و حرفه‌ای برای فروشگاه هستی.');
    }

    /**
     * ارسال پیام کاربر به Groq API و دریافت پاسخ
     *
     * @param string $user_message پیام کاربر
     * @return string پاسخ دریافتی از هوش مصنوعی
     */
    public function process_message($user_message) {
        if (empty($this->api_key)) {
            return 'کلید API گروشاپ تنظیم نشده است. لطفاً به بخش تنظیمات افزونه مراجعه کنید.';
        }

        $body = array(
            'model'    => $this->model,
            'messages' => array(
                array(
                    'role'    => 'system',
                    'content' => $this->system_prompt
                ),
                array(
                    'role'    => 'user',
                    'content' => $user_message
                )
            ),
            'temperature' => 0.7,
            'max_tokens'  => 500
        );

        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body'    => json_encode($body),
            'timeout' => 15
        );

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            return 'خطا در ارتباط با سرور: ' . $response->get_error_message();
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || !isset($data['choices'][0]['message']['content'])) {
            return 'خطا در پردازش پاسخ از هوش مصنوعی. لطفاً کلید API را بررسی کنید.';
        }

        return trim($data['choices'][0]['message']['content']);
    }
}

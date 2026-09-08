jQuery(document).ready(function ($) {
    'use strict';

    var $toggleBtn   = $('#groshop-toggle-btn');
    var $chatWindow  = $('#groshop-chat-window');
    var $closeBtn    = $('#groshop-close-btn');
    var $sendBtn     = $('#groshop-send-btn');
    var $input       = $('#groshop-input');
    var $messagesBody= $('#groshop-messages-body');

    // باز و بسته کردن پنجره چت
    $toggleBtn.on('click', function () {
        $chatWindow.toggleClass('groshop-hidden');
        if (!$chatWindow.hasClass('groshop-hidden')) {
            $input.focus();
        }
    });

    $closeBtn.on('click', function () {
        $chatWindow.addClass('groshop-hidden');
    });

    // ارسال پیام با کلیک روی دکمه یا زدن کلید Enter
    $sendBtn.on('click', function () {
        sendMessage();
    });

    $input.on('keypress', function (e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    function sendMessage() {
        var message = $.trim($input.val());

        if (message === '') {
            return;
        }

        // نمایش پیام کاربر در کادر گفتگو
        appendMessage(message, 'user');
        $input.val('');
        $sendBtn.prop('disabled', true);

        // نمایش وضعیت "در حال تایپ..."
        var $typingIndicator = $('<div class="groshop-msg groshop-msg-bot groshop-typing">در حال پاسخ‌دهی...</div>');
        $messagesBody.append($typingIndicator);
        scrollToBottom();

        // ارسال درخواست به سرور وردپرس از طریق AJAX
        $.ajax({
            url: groshop_data.ajax_url,
            type: 'POST',
            data: {
                action: 'groshop_send_message',
                nonce: groshop_data.nonce,
                message: message
            },
            success: function (response) {
                $typingIndicator.remove();
                $sendBtn.prop('disabled', false);

                if (response.success) {
                    appendMessage(response.data, 'bot');
                } else {
                    appendMessage('خطایی رخ داد: ' + (response.data || 'پاسخی دریافت نشد.'), 'bot');
                }
            },
            error: function () {
                $typingIndicator.remove();
                $sendBtn.prop('disabled', false);
                appendMessage('ارتباط با سرور برقرار نشد. لطفاً اینترنت خود را بررسی کنید.', 'bot');
            }
        });
    }

    function appendMessage(text, sender) {
        var msgClass = sender === 'user' ? 'groshop-msg-user' : 'groshop-msg-bot';
        var $msgDiv  = $('<div></div>').addClass('groshop-msg ' + msgClass).text(text);
        
        $messagesBody.append($msgDiv);
        scrollToBottom();
    }

    function scrollToBottom() {
        $messagesBody.scrollTop($messagesBody[0].scrollHeight);
    }
});

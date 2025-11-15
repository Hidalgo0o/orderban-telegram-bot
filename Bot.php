<?php
// ----------------------- تنظیمات -----------------------
$token = "8546557524:AAHLtWhTzEKt3s2apgbD3uUcSb11e6hWVtI";   // توکن ربات
$support_group_id = "-5060366230";                           // آیدی گروه پشتیبانی
$admin_id = "80315391";                                      // آیدی ادمین
$apiUrl = "https://api.telegram.org/bot$token/";
// -------------------------------------------------------

// دریافت آپدیت تلگرام
$raw = file_get_contents("php://input");
if (!$raw) exit;
$update = json_decode($raw, true);
if (!$update) exit;

// ارسال پیام
function sendMessage($chat_id, $text, $reply_to = null) {
    global $apiUrl;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($reply_to) $params['reply_to_message_id'] = $reply_to;

    file_get_contents($apiUrl . "sendMessage?" . http_build_query($params));
}

// فوروارد کردن پیام
function forwardMessage($to, $from, $msg) {
    global $apiUrl;
    $params = [
        'chat_id' => $to,
        'from_chat_id' => $from,
        'message_id' => $msg
    ];
    file_get_contents($apiUrl . "forwardMessage?" . http_build_query($params));
}

// پردازش پیام
$message = $update['message'] ?? null;

if ($message) {

    $chat_id   = $message['chat']['id'];
    $chat_type = $message['chat']['type'];
    $text      = $message['text'] ?? "";
    $from      = $message['from'] ?? null;
    $from_id   = $from['id'] ?? null;

    // جلوگیری از استفاده در گروه‌های دیگر
    if (($chat_type == "group" || $chat_type == "supergroup") && $chat_id != $support_group_id) {
        exit;
    }

    // پیام‌های خصوصی کاربران
    if ($chat_type == "private") {

        if ($text == "/start") {
            sendMessage($chat_id,
"سلام 👋  
به پشتیبانی اردربان خوش اومدی  
پیامت رو ارسال کن تا همکارانم بررسی کنن.");
            exit;
        }

        // فوروارد پیام کاربر به گروه پشتیبانی
        forwardMessage($support_group_id, $chat_id, $message['message_id']);

        // جواب به کاربر
        sendMessage($chat_id, "پیامت دریافت شد ✅  
پشتیبانی به زودی پاسخ می‌دهد.");

        exit;
    }

    // پیام‌های داخل گروه پشتیبانی
    if ($chat_id == $support_group_id && ($chat_type == "group" || $chat_type == "supergroup")) {

        if (isset($message['reply_to_message'])) {

            $reply_to = $message['reply_to_message'];

            if (isset($reply_to['forward_from']['id'])) {

                $target_user = $reply_to['forward_from']['id'];

                sendMessage(
                    $target_user,
                    "📩 <b>پاسخ پشتیبانی:</b>\n\n" . $text
                );

                sendMessage($chat_id, "✅ پاسخ ارسال شد.", $message['message_id']);
            } else {
                sendMessage(
                    $chat_id,
"❌ نمی‌توانم تشخیص دهم این پیام متعلق به کدام کاربر است.  
فقط روی پیام‌های فوروارد شده از کاربران ریپلای کنید.",
                    $message['message_id']
                );
            }
            exit;
        }

        exit;
    }
}
?>

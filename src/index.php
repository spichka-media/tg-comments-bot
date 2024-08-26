<?php

$env = parse_ini_file('.env');

$lastUpdateId = 0;

$updates = json_decode(file_get_contents('https://api.telegram.org/bot' . $env['BOT_TOKEN'] . '/getUpdates'), true);

foreach ($updates['result'] as $update) {
    $lastUpdateId = $update['update_id'];
    if (!empty($update['message']['is_automatic_forward'])) {
        file_get_contents(
            'https://api.telegram.org/bot' . $env['BOT_TOKEN'] . '/sendMessage',
            false,
            stream_context_create([
                'http' => [
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'method' => 'POST',
                    'content' => http_build_query([
                        'chat_id' => intval($env['DISCUSSION_CHAT_ID']),
                        'text' => $env['COMMENT'],
                        'parse_mode' => 'MarkdownV2',
                        'disable_web_page_preview' => true,
                        'reply_to_message_id' => $update['message']['message_id'],
                    ]),
                ],
            ]),
        );
    }
}

//Reset updates for further runs
if ($lastUpdateId > 0) {
    file_get_contents('https://api.telegram.org/bot' . $env['BOT_TOKEN'] . '/getUpdates?offset=' . ($lastUpdateId + 1));
}

?>

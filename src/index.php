<?php

$env = parse_ini_file('.env');

$lastUpdateId = 0;
$lastMediaGroupId = null;

$updates = json_decode(file_get_contents('https://api.telegram.org/bot' . $env['BOT_TOKEN'] . '/getUpdates'), true);

if (isset($updates['result']) && count($updates['result']) > 0) {
    error_log("[INFO] received updates:\n" . json_encode($updates, JSON_PRETTY_PRINT));
}

foreach ($updates['result'] as $update) {
    $lastUpdateId = $update['update_id'];

    if (!empty($update['message']['media_group_id'])) {
        if ($lastMediaGroupId === $update['message']['media_group_id']) {
            error_log('[INFO] skipped message with the same media_group_id: ' . $update['message']['media_group_id']);
            continue;
        }

        $lastMediaGroupId = $update['message']['media_group_id'];
    }

    if (!empty($update['message']['is_automatic_forward']) && !empty($update['message']['chat']['id']) && $update['message']['chat']['id'] === intval($env['DISCUSSION_CHAT_ID'])) {
        error_log('[INFO] Replying on message ' . $update['message']['message_id']);
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
    error_log('[INFO] reseting updates after lastUpdateId ' . $lastUpdateId);
    file_get_contents('https://api.telegram.org/bot' . $env['BOT_TOKEN'] . '/getUpdates?offset=' . ($lastUpdateId + 1));
}

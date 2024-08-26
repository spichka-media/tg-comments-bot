<?php
    
    $env = parse_ini_file('.env');

    $botToken = $env["BOT_TOKEN"];
    $discussionChatId = intval($env["DISCUSSION_CHAT_ID"]);
    $lastUpdateId = 0;
    $comment = $env["COMMENT"];

    $updates = json_decode(
        file_get_contents("https://api.telegram.org/bot$botToken/getUpdates"),
        true
    );
    
    foreach ($updates["result"] as $update) {
        $lastUpdateId = $update["update_id"];
        if (
            !empty($update["message"]["is_automatic_forward"])
        ) {

            file_get_contents(
                "https://api.telegram.org/bot$botToken/sendMessage",
                false,
                stream_context_create([
                    "http" => [
                        "header" =>
                            "Content-type: application/x-www-form-urlencoded",
                        "method" => "POST",
                        "content" => http_build_query([
                            "chat_id" => $discussionChatId,
                            "text" => $comment,
                            "parse_mode" => "MarkdownV2",
                            "disable_web_page_preview" => true,
                            "reply_to_message_id" =>
                                $update["message"]["message_id"],
                        ]),
                    ],
                ])
            );
        }
    }
    
    //Reset updates for further runs
    if ($lastUpdateId > 0) {
        file_get_contents(
            "https://api.telegram.org/bot$botToken/getUpdates?offset=" .
                ($lastUpdateId + 1)
        );
    }

?>
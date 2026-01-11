<?php
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

$BOT_TOKEN = "PASTE_YOUR_BOT_TOKEN";
$API_URL = "https://api.telegram.org/bot$BOT_TOKEN/";
$usersFile = "users.json";

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        file_put_contents("error.log",
            "[" . date("Y-m-d H:i:s") . "] " . print_r($error, true) . PHP_EOL,
            FILE_APPEND
        );
    }
});

function apiRequest($method, $params = []) {
    global $API_URL;
    $ch = curl_init($API_URL . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($params)
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit("OK");

$message = $update["message"] ?? null;
if (!$message) exit("OK");

$chat_id = $message["chat"]["id"];
$text = $message["text"] ?? "";

$users = json_decode(file_get_contents($usersFile), true);
if (!isset($users[$chat_id])) {
    $users[$chat_id] = ["joined" => time()];
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

if ($text === "/start") {
    apiRequest("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🚀 Bot LIVE on Render (Webhook Mode + Crash Protection)"
    ]);
}

echo "OK";
?>

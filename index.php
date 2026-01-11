<?php
// ======================= CONFIG =======================
$BOT_TOKEN = '8545921888:AAE7ZlLrT0SiZMKfjAkGVM2og19PULckb5s';
$API_URL   = "38609654";
$admin_id  = [8233443753]; // Admin IDs for special commands

$users_file = __DIR__.'/users.json';
$log_file   = __DIR__.'/error.log';

// Make sure storage files exist
if(!file_exists($users_file)) file_put_contents($users_file, '{}');
if(!file_exists($log_file)) file_put_contents($log_file, '');

// =================== HELPER FUNCTIONS ===================
function sendMessage($chat_id, $text, $reply_to = null){
    global $API_URL;
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if($reply_to) $data['reply_to_message_id'] = $reply_to;
    file_get_contents($API_URL."sendMessage?".http_build_query($data));
}

function editMessage($chat_id, $message_id, $text){
    global $API_URL;
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ];
    file_get_contents($API_URL."editMessageText?".http_build_query($data));
}

function log_error($text){
    global $log_file;
    file_put_contents($log_file, "[".date('Y-m-d H:i:s')."] ".$text."\n", FILE_APPEND);
}

// =================== QUEUE & USER STORAGE ===================
$users = json_decode(file_get_contents($users_file), true);
function save_users(){ global $users, $users_file; file_put_contents($users_file, json_encode($users)); }

// =================== WEBHOOK HANDLER ===================
$update = json_decode(file_get_contents('php://input'), true);
if(!$update) exit;

$chat_id = $update['message']['chat']['id'] ?? null;
$message_id = $update['message']['message_id'] ?? null;
$text = $update['message']['text'] ?? '';
$document = $update['message']['document'] ?? null;

// =================== COMMAND HANDLING ===================
if($text){
    if($text == '/start'){
        sendMessage($chat_id, "🚀 Welcome! Send me a file and use /rename to rename it.");
        exit;
    }
    if($text == '/rename'){
        if(!$document){
            sendMessage($chat_id, "❌ Please reply to a file you want to rename.");
            exit;
        }

        $file_id = $document['file_id'];
        $file_name = $document['file_name'];

        // Step 1: Get file path
        $file_path_json = file_get_contents($API_URL."getFile?file_id=$file_id");
        $file_path_arr = json_decode($file_path_json, true);
        $file_path = $file_path_arr['result']['file_path'] ?? null;

        if(!$file_path){
            sendMessage($chat_id, "❌ Failed to get file path.");
            exit;
        }

        // Step 2: Download file in chunks
        $url = "https://api.telegram.org/file/bot$BOT_TOKEN/$file_path";
        $local_file = __DIR__."/temp_".$file_name;
        $fp = fopen($local_file, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        // Step 3: Rename file (example template)
        $new_file_name = "RENAME_".$file_name;
        rename($local_file, __DIR__."/".$new_file_name);

        // Step 4: Send renamed file back
        $post = [
            'chat_id' => $chat_id,
            'document' => new CURLFile(__DIR__."/".$new_file_name)
        ];
        $ch2 = curl_init($API_URL."sendDocument");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $post);
        curl_exec($ch2);
        curl_close($ch2);

        // Step 5: Cleanup
        unlink(__DIR__."/".$new_file_name);

        sendMessage($chat_id, "✅ File renamed successfully.");
        exit;
    }
}

// =================== ADMIN COMMANDS ===================
if(in_array($chat_id, $admin_id)){
    if($text == '/stats'){
        $count = count($users);
        sendMessage($chat_id, "📊 Total users: $count");
    }
}
?>

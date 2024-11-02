<?php
// index.php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = getMessageDbConnection();
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Cache contact names to avoid repeated queries
$contactCache = [];
function getCachedContactName($phone_number) {
    global $contactCache;
    if (isset($contactCache[$phone_number])) {
        return $contactCache[$phone_number];
    }
    $name = getContactName($phone_number);
    $contactCache[$phone_number] = $name ? "$name ($phone_number)" : $phone_number;
    return $contactCache[$phone_number];
}

// Fetch conversations and messages grouped by chat identifier, sorted by timestamp
$query = "
    SELECT chat.ROWID as chat_id, chat.chat_identifier, message.text, 
           message.date AS timestamp, message.is_from_me, handle.id AS sender_number,
           (SELECT GROUP_CONCAT(handle.id, ', ') FROM handle 
            JOIN chat_handle_join ON handle.ROWID = chat_handle_join.handle_id
            WHERE chat_handle_join.chat_id = chat.ROWID) AS participants,
           MAX(message.date) OVER (PARTITION BY chat.ROWID) AS latest_message_date
    FROM message
    JOIN chat_message_join ON message.ROWID = chat_message_join.message_id
    JOIN chat ON chat.ROWID = chat_message_join.chat_id
    LEFT JOIN handle ON handle.ROWID = message.handle_id
    WHERE message.text LIKE :search
    ORDER BY latest_message_date DESC, chat_id, message.date ASC
";

$stmt = $db->prepare($query);
$stmt->execute([':search' => "%$search%"]);
$chats = $stmt->fetchAll(PDO::FETCH_GROUP);

// Convert Apple's timestamp to readable date
function convertAppleTimestamp($timestamp) {
    $apple_epoch = strtotime("2001-01-01 00:00:00");
    return date("Y-m-d H:i:s", $apple_epoch + $timestamp / 1000000000);
}

// Calculate message statistics for each conversation
$message_stats = [];
foreach ($chats as $chat_id => $messages) {
    $outgoing_count = 0;
    $incoming_count = 0;
    foreach ($messages as $message) {
        if ($message['is_from_me']) {
            $outgoing_count++;
        } else {
            $incoming_count++;
        }
    }
    $message_stats[$chat_id] = [
        'outgoing' => $outgoing_count,
        'incoming' => $incoming_count,
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <style>
        /* Styles for clean iOS-like theme with message bubbles */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f1f1f1; 
            margin: 0; 
            padding: 0; 
        }
        .tabs { 
            display: flex; 
            overflow-x: auto; 
            padding: 10px; 
            background-color: #ddd; 
            border-bottom: 1px solid #ccc; 
        }
        .tab { 
            margin: 0 5px; 
            padding: 10px 15px; 
            cursor: pointer; 
            background-color: #eee; 
            border-radius: 15px; 
        }
        .tab.active { 
            background-color: #bbb; 
            font-weight: bold; 
        }
        .messages-container { 
            display: none; 
            padding: 20px; 
            flex-direction: column; 
        }
        .messages-container.active { 
            display: flex; 
        }
        .message { 
            max-width: 70%; 
            padding: 10px; 
            border-radius: 18px; 
            margin: 10px 0; 
            position: relative; 
            font-size: 14px; 
        }
        .message.sent { 
            background-color: #007AFF; 
            color: #FFF; 
            align-self: flex-end; 
            margin-left: auto; 
            border-radius: 18px 18px 0 18px; 
        }
        .message.received { 
            background-color: #E5E5EA; 
            color: #000; 
            margin-right: auto; 
            border-radius: 18px 18px 18px 0; 
        }
        .message-info { 
            font-size: 12px; 
            color: #888; 
            margin-bottom: 2px; 
        }
        .stats { 
            font-weight: bold; 
            margin-bottom: 10px; 
        }
        nav { 
            margin-bottom: 20px; 
            padding: 10px; 
        }
        nav a { 
            margin-right: 10px; 
            text-decoration: none; 
            color: #333; 
        }
        nav a:hover { 
            text-decoration: underline; 
        }
    </style>
    <script>
        function showChat(chatId) {
            document.querySelectorAll('.messages-container').forEach(container => container.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            document.getElementById('chat-' + chatId).classList.add('active');
            document.getElementById('tab-' + chatId).classList.add('active');
        }

        window.onload = function() {
            let firstTab = document.querySelector('.tab');
            if (firstTab) {
                firstTab.click();
            }
        }
    </script>
</head>
<body>

<nav>
    <a href="index.php">Messages</a> | <a href="calls.php">Call History</a>
</nav>

<h1>Messages</h1>
<form method="GET" style="padding: 10px;">
    <input type="text" name="search" placeholder="Search messages" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<div class="tabs">
    <?php foreach ($chats as $chat_id => $messages): ?>
        <?php
            $firstSender = explode(',', $messages[0]['participants'])[0];
            $tabTitle = getCachedContactName($firstSender); // Fetch contact name with number
        ?>
        <div id="tab-<?= $chat_id ?>" class="tab" onclick="showChat(<?= $chat_id ?>)">
            <?= htmlspecialchars($tabTitle) ?>
        </div>
    <?php endforeach; ?>
</div>

<?php foreach ($chats as $chat_id => $messages): ?>
    <div id="chat-<?= $chat_id ?>" class="messages-container">
        <div class="stats">
            Total Messages - Outgoing: <?= $message_stats[$chat_id]['outgoing'] ?>, Incoming: <?= $message_stats[$chat_id]['incoming'] ?>
        </div>
        <?php foreach ($messages as $message): ?>
            <?php
                $displayName = getCachedContactName($message['sender_number']);
            ?>
            <div class="message-info">
                <?= htmlspecialchars($displayName) ?>
                <span><?= convertAppleTimestamp($message['timestamp']) ?></span>
            </div>
            <div class="message <?= $message['is_from_me'] ? 'sent' : 'received' ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

</body>
</html>
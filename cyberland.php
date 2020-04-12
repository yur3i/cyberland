<?php
require __DIR__ . '/vendor/autoload.php';
require_once("RateLimit.php");

$servername = "localhost";
$username   = "cybersql";
// PASSWORD REDACTED
$dbname     = "cyberland";

function post(string $board): void
{
    global $servername;
    global $dbname;
    global $username;
    global $password;

    // Fuck you spamfag (not gonna name you either ;] )
    $torNodes  = file("tornodes", FILE_IGNORE_NEW_LINES);
    if (in_array($_SERVER["REMOTE_ADDR"], $torNodes)) {
        echo "Get lost.";
        exit;
    }
    $rl = new RateLimit();
    $st = $rl->getSleepTime($_SERVER["REMOTE_ADDR"]);
    echo $st;
    if ($st > 0) {
        echo "Please go away.";
    } elseif (!isset($_POST["content"])) {
        echo "Fuck off.";
    } else {
        $reply = intval($_POST["replyTo"] ?? 0);
        $conn = new PDO("mysql:host={$servername};dbname={$dbname}", $username, $password);
        $sql = "INSERT INTO {$board} (content, replyTo, bumpCount, time) VALUES (?,?,?,?)";
        $timeztamp = date("Y-m-d H:i:s");
        $repto = 0;
        $s = $conn->prepare($sql);
        $s->bindParam(4, $timeztamp		          , PDO::PARAM_STR);
        $s->bindParam(3, $repto		            	, PDO::PARAM_INT);
        $s->bindParam(4, $timeztamp   					, PDO::PARAM_STR);
        $s->bindParam(3, $repto     						, PDO::PARAM_INT);
        $s->bindParam(2, $reply                 , PDO::PARAM_INT);
        $s->bindParam(1, $_POST["content"]      , PDO::PARAM_STR);
        $s->execute();
        echo $s->fetch();

        // If the reply wasn't to a board itself, bump the associated reply
        if ($reply != 0) {
            $s = $conn->prepare("UPDATE {$board} SET bumpCount = bumpCount + 1 WHERE id = ?");
            $s->bindParam(1, $reply, PDO::PARAM_INT);
            $s->execute();
            echo $s->fetch();
        }
    }
}

function get(string $board): void
{
    global $servername;
    global $dbname;
    global $username;
    global $password;

    $num = intval($_GET['num'] ?? 50);

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    if (isset($_GET["thread"])) {
        $sql = "SELECT * FROM ".$board." WHERE replyTo=? OR id=? ORDER BY bumpCount DESC LIMIT ?";
        $s = $conn->prepare($sql);
        $s->bindParam(1, $_GET["thread"], PDO::PARAM_INT);
        $s->bindParam(2, $_GET["thread"], PDO::PARAM_INT);
        $s->bindParam(3, $num, PDO::PARAM_INT);
    } else {
        $sql = "SELECT * FROM {$board} ORDER BY bumpCount DESC LIMIT ?";
        $s = $conn->prepare($sql);
        $s->bindParam(1, $num, PDO::PARAM_INT);
    }
    $s->execute();
    $r = $s->fetchAll();
    $a = [];
    // Why is this here again?
    foreach ($r as $result) {
        $a[] = [
            "id"        => $result["id"],
            "content"   => $result["content"],
            "replyTo"   => $result["replyTo"],
            "bumpCount" => $result["bumpCount"],
            "time"      => $result["time"],
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($a);
}

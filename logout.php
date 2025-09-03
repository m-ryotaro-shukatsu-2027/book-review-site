<?php
// セッションを開始
session_start();

// セッション変数をすべて解除
$_SESSION = array();

// セッションクッキーを削除（もし存在すれば）
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最終的にセッションを破壊
session_destroy();

// ログインページにリダイレクト
header("Location: login.php");
exit;
?>

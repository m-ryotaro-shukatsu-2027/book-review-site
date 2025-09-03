<?php
// セッションを開始
session_start();

// メッセージ変数を初期化
$error_message = "";
$success_message = "";

// 登録ページからの成功メッセージを取得
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// ユーザー情報を保存するCSVファイル名
$user_file = 'users.csv';

// フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nickname = $_POST['nickname'];
    $password = $_POST['password'];
    
    $is_authenticated = false;

    // ユーザー情報ファイルが存在するか確認
    if (file_exists($user_file)) {
        // CSVファイルを読み込みモードで開く
        if (($handle = fopen($user_file, "r")) !== FALSE) {
            // CSVの各行をループで処理
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // $data[0]がニックネーム, $data[1]がハッシュ化パスワード
                if (isset($data[0], $data[1])) {
                    $stored_nickname = $data[0];
                    $stored_hash = $data[1];
                    
                    // ニックネームが一致するか確認
                    if ($stored_nickname === $nickname) {
                        // パスワードがハッシュと一致するか確認
                        if (password_verify($password, $stored_hash)) {
                            $is_authenticated = true;
                            break; // 認証成功
                        }
                    }
                }
            }
            fclose($handle);
        }
    }

    if ($is_authenticated) {
        // 認証成功
        $_SESSION['nickname'] = $nickname;
        header("Location: welcome.php");
        exit;
    } else {
        // 認証失敗
        $error_message = "ニックネームまたはパスワードが間違っています。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-xs">
        <form class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4" action="login.php" method="post">
            <h1 class="text-2xl font-bold text-center mb-6 text-gray-700">参考書レビューサイト</h1>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nickname">
                    ニックネーム
                </label>
                <input class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="nickname" name="nickname" type="text" placeholder="登録したニックネーム" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    パスワード
                </label>
                <input class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="password" name="password" type="password" placeholder="パスワード" required>
            </div>
            <div class="flex flex-col items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline w-full mb-4" type="submit">
                    ログイン
                </button>
                <a class="inline-block align-baseline font-bold text-sm text-green-500 hover:text-green-800" href="register.php">
                    アカウントを新規作成
                </a>
            </div>
        </form>
    </div>
</body>
</html>

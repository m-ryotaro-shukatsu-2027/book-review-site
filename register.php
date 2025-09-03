<?php
// セッションを開始
session_start();

// エラーメッセージと成功メッセージを格納する変数を初期化
$error_message = "";
$success_message = "";

// ユーザー情報を保存するCSVファイル名
$user_file = 'users.csv';

// フォームが送信された（POSTリクエストがあった）場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nickname = $_POST['nickname'];
    $password = $_POST['password'];

    // --- バリデーション ---
    if (empty($nickname) || empty($password)) {
        $error_message = "ニックネームとパスワードの両方を入力してください。";
    } elseif (strlen($password) < 8) { // ▼▼▼ パスワードの文字数チェックを追加 ▼▼▼
        $error_message = "パスワードは8文字以上で設定してください。";
    } else {
        // ニックネームが既に存在するかチェック
        $user_exists = false;
        if (file_exists($user_file)) {
            if (($handle = fopen($user_file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (isset($data[0]) && $data[0] === $nickname) {
                        $user_exists = true;
                        break;
                    }
                }
                fclose($handle);
            }
        }

        if ($user_exists) {
            $error_message = "そのニックネームは既に使用されています。";
        } else {
            // パスワードをハッシュ化
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // CSVファイルに新しいユーザー情報を追記
            $handle = fopen($user_file, 'a');
            fputcsv($handle, [$nickname, $hashed_password]);
            fclose($handle);
            
            // 登録成功メッセージを設定し、ログインページへリダイレクト
            $_SESSION['success_message'] = "登録が完了しました。ログインしてください。";
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-xs">
        <form class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4" action="register.php" method="post">
            <h1 class="text-2xl font-bold text-center mb-6 text-gray-700">新規登録</h1>
            
            <?php if (!empty($error_message)): ?>
                <!-- エラーメッセージがある場合に表示 -->
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nickname">
                    ニックネーム
                </label>
                <input class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="nickname" name="nickname" type="text" placeholder="好きなニックネーム" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    パスワード (8文字以上)
                </label>
                <input class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="password" name="password" type="password" placeholder="8文字以上で入力" required>
            </div>
            <div class="flex flex-col items-center justify-between">
                <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline w-full mb-4" type="submit">
                    登録する
                </button>
                <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="login.php">
                    すでにアカウントをお持ちですか？ ログイン
                </a>
            </div>
        </form>
    </div>
</body>
</html>

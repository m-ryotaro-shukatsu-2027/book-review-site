<?php
// セッションを開始
session_start();

// ログインしているかどうかをチェック
if (!isset($_SESSION['nickname'])) {
    header("Location: login.php");
    exit;
}

// --- 初期設定 ---
$nickname = $_SESSION['nickname'];
$upload_dir = 'uploads/';
$posts_csv = 'posts.csv';
$error_message = '';
$success_message = '';
$edit_post_data = null; // 編集対象の投稿データを格納する変数
// カテゴリと科目のリスト
$categories = [
    '高校受験用' => ['国語', '数学', '理科', '英語', '社会'],
    '高校1年生用' => ['現代文', '古文', '漢文', '数１A', '数２B', '数３', '化学', '物理', '生物', '地学', '英語', '日本史', '世界史', '地理', '現代社会'],
    '高校2年生用' => ['現代文', '古文', '漢文', '数１A', '数２B', '数３', '化学', '物理', '生物', '地学', '英語', '日本史', '世界史', '地理', '現代社会'],
    '高校3年生用' => ['現代文', '古文', '漢文', '数１A', '数２B', '数３', '化学', '物理', '生物', '地学', '英語', '日本史', '世界史', '地理', '現代社会'],
    '大学受験用' => ['現代文', '古文', '漢文', '数１A', '数２B', '数３', '化学', '物理', '生物', '地学', '英語', '日本史', '世界史', '地理', '現代社会']
];
$rating_labels = ['おすすめ度', '難易度', '解説の充実度', '網羅性', 'レイアウト', 'コストパフォーマンス'];

// uploadsディレクトリがなければ作成
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- 関数定義 ---
function get_all_posts($file) {
    $posts = [];
    if (file_exists($file)) {
        $handle = fopen($file, 'r');
        while (($data = fgetcsv($handle)) !== FALSE) { $posts[] = $data; }
        fclose($handle);
    }
    return $posts;
}
function save_all_posts($file, $posts) {
    $handle = fopen($file, 'w');
    foreach ($posts as $post) { fputcsv($handle, $post); }
    fclose($handle);
}

// --- POSTリクエストの処理 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 削除処理 ---
    if (isset($_POST['delete_id']) && !empty($_POST['delete_pass'])) {
        $delete_id = $_POST['delete_id'];
        $delete_pass = $_POST['delete_pass'];
        $all_posts = get_all_posts($posts_csv);
        $new_posts = [];
        $post_found = false;
        foreach ($all_posts as $post) {
            if ($post[0] == $delete_id) {
                $post_found = true;
                if (password_verify($delete_pass, $post[5])) {
                    if (!empty($post[2]) && file_exists($upload_dir . $post[2])) { unlink($upload_dir . $post[2]); }
                    $success_message = "投稿ID: {$delete_id} を削除しました。";
                    continue;
                } else { $error_message = "パスワードが違います。"; }
            }
            $new_posts[] = $post;
        }
        if ($post_found && empty($error_message)) { save_all_posts($posts_csv, $new_posts); } 
        elseif (!$post_found) { $error_message = "指定された投稿IDが見つかりません。"; }
    }

    // --- 編集開始処理 ---
    elseif (isset($_POST['edit_id']) && !empty($_POST['edit_pass'])) {
        $edit_id = $_POST['edit_id'];
        $edit_pass = $_POST['edit_pass'];
        $all_posts = get_all_posts($posts_csv);
        foreach ($all_posts as $post) {
            if ($post[0] == $edit_id) {
                if (password_verify($edit_pass, $post[5])) { $edit_post_data = $post; } 
                else { $error_message = "パスワードが違います。"; }
                break;
            }
        }
    }

    // --- 新規投稿・編集更新処理 ---
    elseif (isset($_POST['submit_post'])) {
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $comment = $_POST['comment'];
        $password = $_POST['password'];
        $ratings = [
            'recommendation' => $_POST['recommendation'] ?? 0,
            'difficulty' => $_POST['difficulty'] ?? 0,
            'explanation' => $_POST['explanation'] ?? 0,
            'comprehensiveness' => $_POST['comprehensiveness'] ?? 0,
            'layout' => $_POST['layout'] ?? 0,
            'cost_performance' => $_POST['cost_performance'] ?? 0,
        ];
        $editing_id = $_POST['editing_id'] ?? null;

        // バリデーション
        if (empty($title)) $error_message = "参考書のタイトルを入力してください。";
        elseif (empty($category)) $error_message = "区分を選択してください。";
        elseif (empty($subject)) $error_message = "科目を選択してください。";
        elseif (in_array(0, $ratings, true)) $error_message = "すべての評価項目を選択してください。";
        elseif (empty($comment)) $error_message = "コメントを入力してください。";
        elseif (empty($password) && empty($editing_id)) $error_message = "パスワードを入力してください。";
        elseif (empty($editing_id) && (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK)) $error_message = "画像を選択してください。";

        if (empty($error_message)) {
            $all_posts = get_all_posts($posts_csv);
            if (!empty($editing_id)) { // 編集モード
                foreach ($all_posts as &$post) {
                    if ($post[0] == $editing_id) {
                        $post[3] = $comment;
                        $post[6] = $ratings['recommendation'];
                        $post[7] = $ratings['difficulty'];
                        $post[8] = $ratings['explanation'];
                        $post[9] = $ratings['comprehensiveness'];
                        $post[10] = $ratings['layout'];
                        $post[11] = $ratings['cost_performance'];
                        $post[12] = $title;
                        $post[13] = $category;
                        $post[14] = $subject;
                        if(!empty($password)) $post[5] = password_hash($password, PASSWORD_DEFAULT);
                        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                            if(file_exists($upload_dir . $post[2])) unlink($upload_dir . $post[2]);
                            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                            $new_filename = uniqid(mt_rand(), true) . '.' . $ext;
                            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename);
                            $post[2] = $new_filename;
                        }
                        $success_message = "投稿ID: {$editing_id} を更新しました。";
                        break;
                    }
                }
                save_all_posts($posts_csv, $all_posts);
            } else { // 新規投稿モード
                $last_post = end($all_posts);
                $new_id = $last_post ? $last_post[0] + 1 : 1;
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid(mt_rand(), true) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename);
                $new_post = [
                    $new_id, $nickname, $new_filename, $comment, date("Y-m-d H:i:s"),
                    password_hash($password, PASSWORD_DEFAULT),
                    $ratings['recommendation'], $ratings['difficulty'], $ratings['explanation'],
                    $ratings['comprehensiveness'], $ratings['layout'], $ratings['cost_performance'],
                    $title, $category, $subject
                ];
                $all_posts[] = $new_post;
                save_all_posts($posts_csv, $all_posts);
                $success_message = "新しい投稿が完了しました。";
            }
        }
    }
    
    if (!empty($error_message) || !empty($success_message) || (isset($_POST['submit_post']) && empty($error_message))) {
        $_SESSION['error_message'] = $error_message;
        $_SESSION['success_message'] = $success_message;
        header("Location: welcome.php");
        exit;
    }
}

// --- メッセージ取得 ---
if (isset($_SESSION['error_message'])) { $error_message = $_SESSION['error_message']; unset($_SESSION['error_message']); }
if (isset($_SESSION['success_message'])) { $success_message = $_SESSION['success_message']; unset($_SESSION['success_message']); }

// --- 表示用データ準備 ---
$search_term = trim($_GET['search'] ?? '');
$all_posts = array_reverse(get_all_posts($posts_csv));
$posts_by_category = [];
foreach ($all_posts as $post) {
    $category = $post[13] ?? '未分類';
    $subject = $post[14] ?? '未分類';
    $title = $post[12] ?? 'タイトルなし';
    if (!empty($search_term) && mb_stripos($title, $search_term) === false) { continue; }
    if (!isset($posts_by_category[$category])) { $posts_by_category[$category] = []; }
    if (!isset($posts_by_category[$category][$subject])) { $posts_by_category[$category][$subject] = []; }
    if (!isset($posts_by_category[$category][$subject][$title])) { $posts_by_category[$category][$subject][$title] = []; }
    $posts_by_category[$category][$subject][$title][] = $post;
}
uksort($posts_by_category, function($a, $b) use ($categories) {
    $a_keys = array_keys($categories);
    $a_index = array_search($a, $a_keys);
    $b_index = array_search($b, $a_keys);
    if ($a_index === false) return 1;
    if ($b_index === false) return -1;
    return $a_index - $b_index;
});
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ようこそ！</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .star-rating > input { display: none; }
        .star-rating > label { font-size: 1.75rem; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star-rating > input:checked ~ label, .star-rating:not(:checked) > label:hover, .star-rating:not(:checked) > label:hover ~ label { color: #facc15; }
        .title-link.active { background-color: #eff6ff; color: #1d4ed8; }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-white shadow-md sticky top-0 z-10">
        <nav class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="text-xl font-bold text-gray-700">ようこそ、<?php echo htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8'); ?>さん！</div>
            <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">ログアウト</a>
        </nav>
    </header>

    <main class="container mx-auto px-6 py-8">
        <!-- メッセージ表示エリア -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- 投稿フォーム（折りたたみ式）-->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div id="form-toggle" class="cursor-pointer p-6 flex justify-between items-center">
                 <h2 class="text-2xl font-bold text-gray-800">レビューを投稿・編集する</h2>
                 <svg class="w-6 h-6 transform transition-transform text-gray-500 <?php echo $edit_post_data ? 'rotate-180' : ''; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                 </svg>
            </div>
            <div id="form-container" class="<?php echo $edit_post_data ? '' : 'hidden'; ?> px-6 pb-6 border-t">
                 <form action="welcome.php" method="post" enctype="multipart/form-data" class="pt-6">
                    <input type="hidden" name="editing_id" value="<?php echo $edit_post_data[0] ?? ''; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">参考書のタイトル</label>
                            <input type="text" name="title" id="title" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" placeholder="例：速読英単語 必修編" value="<?php echo htmlspecialchars($edit_post_data[12] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">区分</label>
                                <select name="category" id="category" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" required>
                                    <option value="">選択</option>
                                    <?php foreach ($categories as $cat => $subjects): 
                                        $selected = (isset($edit_post_data[13]) && $edit_post_data[13] == $cat) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div>
                                <label for="subject" class="block text-gray-700 text-sm font-bold mb-2">科目</label>
                                <select name="subject" id="subject" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" required>
                                    <option value="">区分を選択</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4 border p-4 rounded-lg">
                        <?php
                        $rating_keys = ['recommendation', 'difficulty', 'explanation', 'comprehensiveness', 'layout', 'cost_performance'];
                        $current_ratings = [
                            $edit_post_data[6] ?? 0, $edit_post_data[7] ?? 0, $edit_post_data[8] ?? 0,
                            $edit_post_data[9] ?? 0, $edit_post_data[10] ?? 0, $edit_post_data[11] ?? 0
                        ];
                        foreach ($rating_labels as $index => $label):
                            $key = $rating_keys[$index];
                        ?>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo $label; ?></label>
                            <div class="star-rating flex flex-row-reverse justify-end">
                                <?php for ($i = 5; $i >= 1; $i--): 
                                    $checked = ($current_ratings[$index] == $i) ? 'checked' : '';
                                ?>
                                    <input id="<?php echo $key . $i; ?>" name="<?php echo $key; ?>" type="radio" value="<?php echo $i; ?>" class="sr-only" required <?php echo $checked; ?>><label for="<?php echo $key . $i; ?>">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-4">
                        <label for="image" class="block text-gray-700 text-sm font-bold mb-2">参考書の表紙画像 <?php echo $edit_post_data ? '(変更する場合のみ選択)' : ''; ?></label>
                        <input type="file" name="image" id="image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" <?php echo $edit_post_data ? '' : 'required'; ?>>
                    </div>
                    <div class="mb-4">
                        <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">コメント</label>
                        <textarea name="comment" id="comment" rows="4" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" required><?php echo htmlspecialchars($edit_post_data[3] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">パスワード <?php echo $edit_post_data ? '(変更する場合のみ入力)' : ''; ?></label>
                        <input type="password" name="password" id="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" placeholder="投稿を管理するためのパスワード" <?php echo $edit_post_data ? '' : 'required'; ?>>
                    </div>
                    <button type="submit" name="submit_post" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                        <?php echo $edit_post_data ? '更新する' : '投稿する'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- 2カラムレイアウト -->
        <div class="flex flex-col md:flex-row md:space-x-8">
            <!-- 左カラム：タイトル一覧 -->
            <aside class="md:w-1/3 lg:w-1/4 mb-8 md:mb-0">
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">参考書一覧</h2>
                    <form action="welcome.php" method="get" class="mb-4">
                         <div class="flex items-center border rounded-lg overflow-hidden">
                             <input class="appearance-none bg-transparent border-none w-full text-gray-700 p-2" type="text" name="search" placeholder="タイトルで検索" value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>">
                             <button class="flex-shrink-0 bg-blue-500 hover:bg-blue-700 text-white p-2" type="submit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                             </button>
                         </div>
                    </form>
                    <nav class="space-y-4">
                        <?php if (empty($posts_by_category)): ?>
                             <p class="text-center text-gray-500 p-4">
                                <?php if (!empty($search_term)): ?>
                                    一致する参考書はありません。
                                <?php else: ?>
                                    まだ投稿がありません。
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <?php foreach ($posts_by_category as $category => $subjects): ?>
                                <div>
                                    <div class="category-toggle cursor-pointer flex justify-between items-center font-bold text-gray-600 px-3 pb-2 border-b">
                                        <span><?php echo $category; ?></span>
                                        <svg class="w-5 h-5 transform transition-transform text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                    <div class="subjects-container hidden mt-2 space-y-2">
                                        <?php foreach ($subjects as $subject => $titles): ?>
                                            <div class="pl-2">
                                                <div class="subject-toggle cursor-pointer flex justify-between items-center font-semibold text-gray-500 text-sm p-1">
                                                    <span><?php echo $subject; ?></span>
                                                    <svg class="w-4 h-4 transform transition-transform text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </div>
                                                <div class="titles-container hidden mt-1 space-y-1 pl-2 border-l-2">
                                                <?php foreach ($titles as $title => $reviews): ?>
                                                <a href="#" class="title-link flex justify-between items-center p-2 rounded-lg hover:bg-gray-200 transition-colors" data-target="reviews-<?php echo md5($title); ?>">
                                                    <span class="text-gray-700 flex-1 pr-2"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="text-xs bg-gray-200 text-gray-600 font-semibold rounded-full px-2 py-1 flex-shrink-0"><?php echo count($reviews); ?></span>
                                                </a>
                                                <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </nav>
                </div>
            </aside>

            <!-- 右カラム：レビュー詳細 -->
            <div class="md:w-2/3 lg:w-3/4">
                <?php if (empty($posts_by_category)): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md text-center text-gray-500">
                        レビューを選択してください。
                    </div>
                <?php else: ?>
                    <?php foreach ($posts_by_category as $category => $subjects): ?>
                        <?php foreach ($subjects as $subject => $titles): ?>
                            <?php foreach ($titles as $title => $reviews): ?>
                            <div id="reviews-<?php echo md5($title); ?>" class="review-content hidden bg-white p-6 rounded-lg shadow-md">
                                <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-4"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php
                                    $totals = array_fill(0, 6, 0);
                                    foreach ($reviews as $review) { 
                                        for ($i = 0; $i < 6; $i++) {
                                            $totals[$i] += (int)($review[6 + $i] ?? 0);
                                        }
                                    }
                                    $review_count = count($reviews);
                                    $averages = [];
                                    for ($i = 0; $i < 6; $i++) {
                                        $averages[] = $review_count > 0 ? round($totals[$i] / $review_count, 1) : 0;
                                    }
                                ?>
                                <div class="w-full max-w-md mx-auto mb-6">
                                    <canvas class="radar-chart"
                                        data-ratings="<?php echo htmlspecialchars(json_encode($averages), ENT_QUOTES, 'UTF-8'); ?>">
                                    </canvas>
                                </div>
                                <div class="space-y-6">
                                <?php foreach ($reviews as $post): ?>
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <div class="flex items-center text-sm mt-1 flex-wrap">
                                                    <span class="font-bold text-gray-700 mr-2"><?php echo htmlspecialchars($post[1], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="text-gray-500 mr-2">|</span>
                                                    <span class="text-gray-500 mr-2"><?php echo htmlspecialchars($post[4], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="text-gray-500 mr-2">|</span>
                                                    <span class="font-bold text-gray-700">ID: <?php echo htmlspecialchars($post[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                                                <?php foreach ($rating_labels as $index => $label): ?>
                                                <div class="flex items-center justify-end">
                                                    <span class="text-xs font-bold text-gray-600 mr-2"><?php echo $label; ?>:</span>
                                                    <div class="flex">
                                                    <?php
                                                    $rating = $post[6 + $index] ?? 0;
                                                    for ($i = 1; $i <= 5; $i++):
                                                        echo $i <= $rating ? '<span class="text-yellow-400 text-md">★</span>' : '<span class="text-gray-300 text-md">★</span>';
                                                    endfor;
                                                    ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:space-x-6">
                                            <div class="sm:w-1/4 flex-shrink-0">
                                                <img src="<?php echo $upload_dir . htmlspecialchars($post[2], ENT_QUOTES, 'UTF-8'); ?>" alt="投稿画像" class="rounded-lg w-full h-auto object-contain" style="max-height: 220px;">
                                            </div>
                                            <div class="sm:w-3/4 mt-4 sm:mt-0">
                                                <div class="border p-4 rounded-lg bg-gray-50 h-full">
                                                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($post[3], ENT_QUOTES, 'UTF-8')); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex space-x-4 mt-4">
                                            <button type="button" class="action-btn bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-sm" data-action="delete" data-id="<?php echo $post[0]; ?>">削除</button>
                                            <button type="button" class="action-btn bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm" data-action="edit" data-id="<?php echo $post[0]; ?>">編集</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                             <div id="review-placeholder" class="bg-white p-6 rounded-lg shadow-md text-center text-gray-500">
                                ← 左のリストから参考書を選択してください。
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- パスワード入力モーダル -->
    <div id="password-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm">
            <h3 id="modal-title" class="text-xl font-bold mb-4">パスワードを入力</h3>
            <form id="modal-form" action="welcome.php" method="post">
                <input type="hidden" id="modal-post-id-input" name="">
                <div>
                    <label for="modal-password-input" class="block text-gray-700 text-sm font-bold mb-2">投稿時のパスワード</label>
                    <input type="password" id="modal-password-input" name="" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700" required autofocus>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" id="modal-close-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">キャンセル</button>
                    <button type="submit" id="modal-submit-btn" class="text-white font-bold py-2 px-4 rounded-lg">実行</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categoriesData = <?php echo json_encode($categories); ?>;
            const editPostData = <?php echo json_encode($edit_post_data); ?>;
            const ratingLabels = <?php echo json_encode($rating_labels); ?>;

            const categorySelect = document.getElementById('category');
            const subjectSelect = document.getElementById('subject');
            
            function updateSubjectOptions(selectedCategory, selectedSubject = null) {
                subjectSelect.innerHTML = '<option value="">科目を選択</option>';
                if (selectedCategory && categoriesData[selectedCategory]) {
                    categoriesData[selectedCategory].forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        if (subject === selectedSubject) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                }
            }

            categorySelect.addEventListener('change', () => {
                updateSubjectOptions(categorySelect.value);
            });

            if (editPostData) {
                const selectedCategory = editPostData[13];
                const selectedSubject = editPostData[14];
                if (selectedCategory) {
                    categorySelect.value = selectedCategory;
                    updateSubjectOptions(selectedCategory, selectedSubject);
                }
            }
            
            const titleLinks = document.querySelectorAll('.title-link');
            const reviewContents = document.querySelectorAll('.review-content');
            const placeholder = document.getElementById('review-placeholder');
            const formToggle = document.getElementById('form-toggle');
            const formContainer = document.getElementById('form-container');
            const modal = document.getElementById('password-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalForm = document.getElementById('modal-form');
            const modalPostIdInput = document.getElementById('modal-post-id-input');
            const modalPasswordInput = document.getElementById('modal-password-input');
            const modalSubmitBtn = document.getElementById('modal-submit-btn');
            const modalCloseBtn = document.getElementById('modal-close-btn');
            const actionButtons = document.querySelectorAll('.action-btn');

            if (formToggle) {
                formToggle.addEventListener('click', () => {
                    formContainer.classList.toggle('hidden');
                    formToggle.querySelector('svg').classList.toggle('rotate-180');
                });
            }
            
            document.querySelectorAll('.category-toggle, .subject-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const container = toggle.nextElementSibling;
                    const icon = toggle.querySelector('svg');

                    container.classList.toggle('hidden');
                    if (icon) {
                        icon.classList.toggle('rotate-180');
                    }
                });
            });

            titleLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.dataset.target;
                    const targetContent = document.getElementById(targetId);
                    const isAlreadyActive = link.classList.contains('active');
                    reviewContents.forEach(content => content.classList.add('hidden'));
                    titleLinks.forEach(l => l.classList.remove('active'));
                    if (isAlreadyActive) {
                        if (placeholder) placeholder.classList.remove('hidden');
                    } else {
                        if (placeholder) placeholder.classList.add('hidden');
                        link.classList.add('active');
                        if (targetContent) {
                            targetContent.classList.remove('hidden');
                        }
                    }
                });
            });

            const openModal = (action, postId) => {
                modalForm.reset();
                modalPostIdInput.value = postId;
                if (action === 'delete') {
                    modalTitle.textContent = '投稿の削除';
                    modalPostIdInput.name = 'delete_id';
                    modalPasswordInput.name = 'delete_pass';
                    modalSubmitBtn.textContent = '削除';
                    modalSubmitBtn.className = 'bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg';
                } else if (action === 'edit') {
                    modalTitle.textContent = '投稿の編集';
                    modalPostIdInput.name = 'edit_id';
                    modalPasswordInput.name = 'edit_pass';
                    modalSubmitBtn.textContent = '編集';
                    modalSubmitBtn.className = 'bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg';
                }
                modal.classList.remove('hidden');
                modalPasswordInput.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
            };

            actionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const action = button.dataset.action;
                    const postId = button.dataset.id;
                    openModal(action, postId);
                });
            });

            modalCloseBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Radar Chart Initialization
            const chartCanvases = document.querySelectorAll('.radar-chart');
            chartCanvases.forEach(canvas => {
                const ratings = JSON.parse(canvas.dataset.ratings);
                new Chart(canvas, {
                    type: 'radar',
                    data: {
                        labels: ratingLabels,
                        datasets: [{
                            label: '平均評価',
                            data: ratings,
                            fill: true,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgb(54, 162, 235)',
                            pointBackgroundColor: 'rgb(54, 162, 235)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(54, 162, 235)'
                        }]
                    },
                    options: {
                        scales: {
                            r: {
                                angleLines: { display: true },
                                suggestedMin: 0,
                                suggestedMax: 5,
                                ticks: { 
                                    stepSize: 1,
                                    backdropColor: 'rgba(255, 255, 255, 0.75)'
                                },
                                pointLabels: {
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    color: '#1f2937'
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            });
        });
    </script>

</body>
</html>

<?php
ini_set("max_execution_time", 0);
ini_set("memory_limit", "4096M");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("check_viewport.php");
require_once("check_img_links.php");
require_once("check_svg_image_size.php");

$base_dir = "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/";
$log_file = "C:/xampp/htdocs/working/10_output_logs/check_viewport_imglog_all.txt";

// ログ初期化
file_put_contents($log_file, "【viewport / imgリンク / SVGサイズ チェックログ（全冊まとめ）】\r\n\r\n");

// ===== 「タイトル」ディレクトリを列挙する =====
// 例: 02_xhtmls/cheku/【タイトルA】, 02_xhtmls/cheku/【タイトルB】 ...
$title_dirs = [];

// まず base_dir 直下のディレクトリを取る（例: cheku など）
$parents = array_filter(glob($base_dir . '*'), 'is_dir');

foreach ($parents as $pdir) {
    // その直下のサブディレクトリ（= 実際のタイトルフォルダ）を拾う
    $children = array_filter(glob($pdir . '/*'), 'is_dir');
    if ($children) {
        foreach ($children as $cdir) {
            $title_dirs[] = $cdir;
        }
    } else {
        // 親直下に直接 xhtml 群がある構成にも一応対応
        $title_dirs[] = $pdir;
    }
}

// 自然順で安定化
natsort($title_dirs);

// HTML出力開始
echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>チェックログ</title></head><body>";
echo "<h2>✅ 各タイトル別チェック結果</h2><hr>";

// タイトルごとに処理
foreach ($title_dirs as $dir) {
    $book_name = basename($dir);

    // 画面見出し
    echo "<h3>📘 " . htmlspecialchars($book_name) . "</h3>";
    echo "<ul>";

    // ログ見出し（タイトル名）
    file_put_contents($log_file, "📘 {$book_name}\r\n", FILE_APPEND);

    // Viewportチェック
    echo "<li><strong>✅ Viewportチェック</strong><ul>\n";
    file_put_contents($log_file, "Viewportチェック\r\n", FILE_APPEND);
    check_viewport($dir, $log_file);
    echo "</ul></li>\n";

    // imgリンクチェック
    echo "<li><strong>✅ imgリンクチェック</strong><ul>\n";
    file_put_contents($log_file, "imgリンクチェック\r\n", FILE_APPEND);
    check_img_links($dir, $log_file);
    echo "</ul></li>\n";

    // SVGサイズチェック
    echo "<li><strong>✅ SVGサイズチェック</strong><ul>\n";
    file_put_contents($log_file, "SVGサイズチェック\r\n", FILE_APPEND);
    check_svg_size($dir, $log_file);
    echo "</ul></li>\n";

    echo "</ul><hr>";
    file_put_contents($log_file, "\r\n", FILE_APPEND);
}

echo "<br /><strong>✅ 全チェック完了</strong><br />";
echo "<a href='/working/10_output_logs/check_viewport_imglog_all.txt' target='_blank'>📄 ログファイルを開く</a>";
echo "</body></html>";

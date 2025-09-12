<?php
ini_set("max_execution_time", 0);
ini_set("memory_limit", "512M");

require_once("check_viewport.php");
require_once("check_img_links.php");
require_once("check_svg_image_size.php");

$base_dir = "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/";
$log_file = "C:/xampp/htdocs/working/10_output_logs/check_viewport_imglog_all.txt";

// ログ初期化
file_put_contents($log_file, "【viewport / imgリンク / SVGサイズ チェックログ（全冊まとめ）】\r\n\r\n");

// 対象ディレクトリ一覧取得（フォルダのみ）
$subdirs = array_filter(glob($base_dir . '*'), 'is_dir');

foreach ($subdirs as $dir) {
    $book_name = basename($dir);
    echo "<h3>📘 $book_name</h3>";
    echo "<ul>";

    // ログに見出しを出力
    file_put_contents($log_file, "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\r\n", FILE_APPEND);
    file_put_contents($log_file, "【📘 $book_name のチェック】\r\n", FILE_APPEND);
    file_put_contents($log_file, "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\r\n\r\n", FILE_APPEND);

    echo "<li>✅ Viewportチェック</li>";
    check_viewport($dir, $log_file);

    echo "<li>✅ imgリンクチェック</li>";
    check_img_links($dir, $log_file);

    echo "<li>✅ SVGサイズチェック</li>";
    check_svg_size($dir, $log_file);  // ← 新たに追加

    echo "</ul><br />";
}

echo "<br /><strong>✅ 全チェック完了</strong><br />";
echo "<a href='/working/10_output_logs/check_viewport_imglog_all.txt' target='_blank'>📄 ログファイルを開く</a>";
?>

<?php
ini_set("max_execution_time", 0);
ini_set("memory_limit", "512M");

require_once("check_viewport.php");
require_once("check_img_links.php");

// チェック対象のルートディレクトリ
$base_dir = "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/";

// ログファイル出力先
$log_file = "C:/xampp/htdocs/working/10_output_logs/check_viewport_imglog.txt";

// ログファイル初期化
file_put_contents($log_file, "【viewport / imgリンク チェックログ】\r\n\r\n");

// viewportチェック実行
echo "<h2>✅ Viewportチェック</h2>";
check_viewport($base_dir, $log_file);

// imgリンクチェック実行
echo "<h2>✅ imgリンクチェック</h2>";
check_img_links($base_dir, $log_file);

echo "<br /><strong>✅ 全チェック完了</strong><br />";
echo "<a href='/working/10_output_logs/check_viewport_imglog.txt' target='_blank'>ログファイルを開く</a>";
?>

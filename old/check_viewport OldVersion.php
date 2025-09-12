<?php
function check_viewport($base_dir, $logfile_path) {
    $log = "";

    // XHTMLファイルを再帰的に検索
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.xhtml$/', $file->getFilename())) {
            $filepath = $file->getPathname();
            $relative_path = str_replace($base_dir, '', $filepath);

            // XHTML内容の読み込み
            $contents = file_get_contents($filepath);
            if (!$contents) continue;

            // viewport抽出
            if (preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*content=["\']width=(\d+),\s*height=(\d+)["\']/', $contents, $matches)) {
                $vw = $matches[1];
                $vh = $matches[2];
                echo "✔ {$relative_path} : viewport = {$vw}x{$vh}<br />";
                $log .= "{$relative_path} : viewport = {$vw}x{$vh}\r\n";
            } else {
                echo "❌ {$relative_path} : viewport記述なし<br />";
                $log .= "{$relative_path} : viewport記述なし\r\n";
            }
        }
    }

    file_put_contents($logfile_path, $log, FILE_APPEND);
}
?>

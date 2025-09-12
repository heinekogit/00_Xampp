<?php
/**
 * ファイル内の <meta name="viewport" content="..."> を検出し、width/height を表示・記録
 * ・属性順不同に対応（name/content の順序、width/height の順序）
 * ・各 .xhtml を再帰的に探索
 * ・画面には <li> 行で出力、ログにも追記
 */
function check_viewport($base_dir, $log_file) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS)
    );

    $found = false;

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        if (!preg_match('/\.xhtml$/i', $file->getFilename())) continue;

        $found = true;
        $filepath = $file->getPathname();
        $content  = @file_get_contents($filepath);
        if ($content === false) continue;

        // 相対パス（スラッシュ統一）
        $rel_path = str_replace('\\', '/', str_replace($base_dir, '', $filepath));

        // meta viewport 抽出：まず name="viewport" を特定し、content属性から width/height を拾う
        // name と content の順序、さらに content 内の width/height の順序にも対応
        $line = "";

        if (preg_match('/<meta\b[^>]*\bname=["\']viewport["\'][^>]*>/i', $content, $m)) {
            $meta_tag = $m[0];

            // content="..." を抜き出し
            if (preg_match('/\bcontent=["\']([^"\']+)["\']/i', $meta_tag, $cm)) {
                $content_val = $cm[1];

                $vw = null; $vh = null;
                if (preg_match('/\bwidth\s*=\s*(\d+)/i', $content_val, $wm))  $vw = (int)$wm[1];
                if (preg_match('/\bheight\s*=\s*(\d+)/i', $content_val, $hm)) $vh = (int)$hm[1];

                if ($vw !== null && $vh !== null) {
                    $line = "✔ {$rel_path} : viewport = {$vw}x{$vh}";
                } else {
                    $line = "❌ {$rel_path} : viewport記述あり（width/height 不明）";
                }
            } else {
                $line = "❌ {$rel_path} : viewport記述あり（content属性なし）";
            }
        } else {
            $line = "❌ {$rel_path} : viewport記述なし";
        }

        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
    }

    if (!$found) {
        $line = "❌ Viewportチェック対象ファイルが見つかりません";
        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
    }
}

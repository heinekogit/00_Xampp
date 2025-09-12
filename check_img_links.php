<?php
/**
 * .xhtml 内の画像参照を検出し、存在とサイズ、viewport との一致をチェック
 * ・<img src="..."> と <image xlink:href="..."> の両方に対応
 * ・viewport はファイル内の <meta name="viewport"> の content から width/height を抽出（順不同対応）
 * ・画面には <li> 行で出力、ログにも追記
 */
function check_img_links($base_dir, $log_file) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS)
    );

    $any_found = false;

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        if (!preg_match('/\.xhtml$/i', $file->getFilename())) continue;

        $any_found = true;

        $filepath = $file->getPathname();
        $content  = @file_get_contents($filepath);
        if ($content === false) continue;

        $rel_path = str_replace('\\', '/', str_replace($base_dir, '', $filepath));

        // ===== ビューポート（順不同対応） =====
        $vw = null; $vh = null;
        if (preg_match('/<meta\b[^>]*\bname=["\']viewport["\'][^>]*>/i', $content, $m)) {
            $meta_tag = $m[0];
            if (preg_match('/\bcontent=["\']([^"\']+)["\']/i', $meta_tag, $cm)) {
                $c = $cm[1];
                if (preg_match('/\bwidth\s*=\s*(\d+)/i', $c, $wm))  $vw = (int)$wm[1];
                if (preg_match('/\bheight\s*=\s*(\d+)/i', $c, $hm)) $vh = (int)$hm[1];
            }
        }

        // ===== 画像参照抽出（<img> と <image xlink:href>）=====
        $srcs = [];

        if (preg_match_all('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $content, $mm)) {
            $srcs = array_merge($srcs, $mm[1]);
        }
        if (preg_match_all('/<image\b[^>]*\bxlink:href=["\']([^"\']+)["\']/i', $content, $sm)) {
            $srcs = array_merge($srcs, $sm[1]);
        }

        // 重複削除
        $srcs = array_values(array_unique($srcs));

        if (count($srcs) === 0) {
            $line = "❌ {$rel_path} : 画像タグが存在しません";
            echo "<li>" . htmlspecialchars($line) . "</li>\n";
            file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
            continue;
        }

        if (count($srcs) > 1) {
            $line = "⚠ {$rel_path} : 複数画像タグがあります（" . count($srcs) . "個）";
            echo "<li>" . htmlspecialchars($line) . "</li>\n";
            file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
        }

        foreach ($srcs as $src) {
            // 画像実体の存在確認
            $image_path = realpath(dirname($filepath) . DIRECTORY_SEPARATOR . $src);
            if (!$image_path || !file_exists($image_path)) {
                $line = "❌ {$rel_path} : リンク切れ画像 → {$src}";
                echo "<li>" . htmlspecialchars($line) . "</li>\n";
                file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
                continue;
            }

            // 画像サイズ取得
            $size = @getimagesize($image_path);
            if ($size === false) {
                $line = "❌ {$rel_path} : {$src} → 画像サイズ取得失敗";
                echo "<li>" . htmlspecialchars($line) . "</li>\n";
                file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
                continue;
            }

            $w = (int)$size[0];
            $h = (int)$size[1];

            $line = "✔ {$rel_path} : {$src} → 画像サイズ = {$w}x{$h}";
            echo "<li>" . htmlspecialchars($line) . "</li>\n";
            file_put_contents($log_file, $line . "\r\n", FILE_APPEND);

            // viewport と実画像サイズの比較（両方取れている場合のみ）
            if ($vw !== null && $vh !== null && ($vw !== $w || $vh !== $h)) {
                $line = "❗ サイズ不一致：viewport={$vw}x{$vh} / 画像={$w}x{$h}";
                echo "<li>" . htmlspecialchars($line) . "</li>\n";
                file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
            }
        }

        // メモリ/キャッシュ掃除（大量処理の安定化）
        clearstatcache();
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    if (!$any_found) {
        $line = "❌ imgリンクチェック対象ファイルが見つかりません";
        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
    }
}

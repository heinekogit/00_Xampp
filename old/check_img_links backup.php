<?php
function check_img_links($base_dir, $logfile_path) {
    $log = "";

    // XHTMLファイルを再帰的に検索
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.xhtml$/', $file->getFilename())) {
            $filepath = $file->getPathname();
            $relative_path = str_replace($base_dir, '', $filepath);
            $contents = file_get_contents($filepath);
            if (!$contents) continue;

            // viewport 抽出
            $vw = $vh = null;
            if (preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*content=["\']width=(\d+),\s*height=(\d+)["\']/', $contents, $vp_match)) {
                $vw = $vp_match[1];
                $vh = $vp_match[2];
            }

            // <img src="..."> 抽出
            preg_match_all('/<img[^>]*src=["\']([^"\']+)["\']/', $contents, $img_matches);
            // <image xlink:href="..."> 抽出
            preg_match_all('/<image[^>]*xlink:href=["\']([^"\']+)["\']/', $contents, $svg_matches);

            $src_paths = array_merge($img_matches[1], $svg_matches[1]);

            if (count($src_paths) === 0) {
                echo "❌ {$relative_path} : 画像タグが存在しません<br />";
                $log .= "{$relative_path} : 画像タグが存在しません\r\n";
                continue;
            } elseif (count($src_paths) > 1) {
                echo "⚠ {$relative_path} : 複数画像タグがあります（" . count($src_paths) . "個）<br />";
                $log .= "{$relative_path} : 複数画像タグがあります（" . count($src_paths) . "個）\r\n";
            }

            foreach ($src_paths as $src) {
                $image_path = realpath(dirname($filepath) . '/' . $src);
                if (!$image_path || !file_exists($image_path)) {
                    echo "❌ {$relative_path} : リンク切れ画像 → {$src}<br />";
                    $log .= "{$relative_path} : リンク切れ画像 → {$src}\r\n";
                    continue;
                }

                $size = getimagesize($image_path);
                if ($size) {
                    $w = $size[0];
                    $h = $size[1];
                    echo "✔ {$relative_path} : {$src} → 画像サイズ = {$w}x{$h}<br />";
                    $log .= "{$relative_path} : {$src} → 画像サイズ = {$w}x{$h}\r\n";

                    // viewportと比較
                    if ($vw && $vh && ($vw != $w || $vh != $h)) {
                        echo "❗ サイズ不一致：viewport={$vw}x{$vh} / 画像={$w}x{$h}<br />";
                        $log .= "{$relative_path} : ❗ サイズ不一致 viewport={$vw}x{$vh} / 画像={$w}x{$h}\r\n";
                    }
                } else {
                    echo "❌ {$relative_path} : {$src} → 画像サイズ取得失敗<br />";
                    $log .= "{$relative_path} : {$src} → 画像サイズ取得失敗\r\n";
                }
            }
        }
    }

    file_put_contents($logfile_path, $log, FILE_APPEND);
}
?>

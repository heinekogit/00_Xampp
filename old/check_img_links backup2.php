<?php
function check_img_links($base_dir, $logfile_path) {
    $log = "";

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

            // 画像パス抽出
            $src_paths = [];
            $images = [];

            // <img> タグ
            if (preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*?>/i', $contents, $img_matches, PREG_SET_ORDER)) {
                foreach ($img_matches as $match) {
                    $src_paths[] = $match[1];
                }
            }

            // <image> タグ（SVG内）
            if (preg_match_all('/<image\b[^>]*?xlink:href=["\']([^"\']+)["\'][^>]*?>/i', $contents, $svg_matches, PREG_SET_ORDER)) {
                foreach ($svg_matches as $match) {
                    $src_paths[] = $match[1];
                }
            }

            if (count($src_paths) === 0) {
                echo "❌ {$relative_path} : SVGまたはimageタグが見つかりません<br />";
                $log .= "{$relative_path} : SVGまたはimageタグが見つかりません\r\n";
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
                if (!$size) {
                    echo "❌ {$relative_path} : {$src} → 画像サイズ取得失敗<br />";
                    $log .= "{$relative_path} : {$src} → 画像サイズ取得失敗\r\n";
                    continue;
                }

                $w = $size[0];
                $h = $size[1];
                echo "✔ {$relative_path} : {$src} → 画像サイズ = {$w}x{$h}<br />";
                $log .= "{$relative_path} : {$src} → 画像サイズ = {$w}x{$h}\r\n";

                // viewportとのサイズ比較
                if ($vw && $vh && ($vw != $w || $vh != $h)) {
                    echo "❗ サイズ不一致：viewport={$vw}x{$vh} / 画像={$w}x{$h}<br />";
                    $log .= "{$relative_path} : ❗ サイズ不一致 viewport={$vw}x{$vh} / 画像={$w}x{$h}\r\n";
                }

// ✅ この下に追加（1画像処理の末尾）
    unset($size);
    clearstatcache();
    gc_collect_cycles();

            }
        }
    }

    file_put_contents($logfile_path, $log, FILE_APPEND);
}
?>

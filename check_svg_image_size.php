<?php
/**
 * .xhtml 内の <svg viewBox="0 0 W H"> と <image width="W" height="H"> を検出し、数値を比較
 * ・両方取得できたら数値を表示し、一致しない場合は警告を追加
 * ・取得できない場合はエラー表示
 * ・画面には <li> 行で出力、ログにも追記
 */
function check_svg_size($base_dir, $log_file) {
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

        $rel_path = str_replace('\\', '/', str_replace($base_dir, '', $filepath));

        // viewBox 抽出：0 0 W H
        $vb_w = null; $vb_h = null;
        if (preg_match('/<svg\b[^>]*\bviewBox=["\']\s*0\s+0\s+(\d+)\s+(\d+)\s*["\']/is', $content, $vb)) {
            $vb_w = (int)$vb[1];
            $vb_h = (int)$vb[2];
        }

        // <image> の width/height 抽出（順序は問わない）
        $im_w = null; $im_h = null;
        if (preg_match('/<image\b[^>]*\bwidth=["\'](\d+)["\'][^>]*\bheight=["\'](\d+)["\']/is', $content, $im)) {
            $im_w = (int)$im[1];
            $im_h = (int)$im[2];
        } elseif (preg_match('/<image\b[^>]*\bheight=["\'](\d+)["\'][^>]*\bwidth=["\'](\d+)["\']/is', $content, $im2)) {
            $im_h = (int)$im2[1];
            $im_w = (int)$im2[2];
        }

        if ($vb_w !== null && $vb_h !== null && $im_w !== null && $im_h !== null) {
            $line = "✔ {$rel_path} : SVG = {$vb_w}x{$vb_h} / image = {$im_w}x{$im_h}";
            echo "<li>" . htmlspecialchars($line) . "</li>\n";
            file_put_contents($log_file, $line . "\r\n", FILE_APPEND);

            if ($vb_w !== $im_w || $vb_h !== $im_h) {
                $warn = "❗ サイズ不一致：viewBox={$vb_w}x{$vb_h} / image属性={$im_w}x{$im_h}";
                echo "<li>" . htmlspecialchars($warn) . "</li>\n";
                file_put_contents($log_file, $warn . "\r\n", FILE_APPEND);
            }
        } else {
            $line = "❌ {$rel_path} : SVGまたはimageサイズが取得できません";
            echo "<li>" . htmlspecialchars($line) . "</li>\n";
            file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
        }

        // 大量処理の安定化
        clearstatcache();
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    if (!$found) {
        $line = "❌ SVGチェック対象ファイルが見つかりません";
        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        file_put_contents($log_file, $line . "\r\n", FILE_APPEND);
    }
}

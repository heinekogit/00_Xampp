<?php
/**
 * 画像メタ削除 + sRGB変換 + 強制RGB化（グレースケール含む）
 * - 対象: JPG/JPEG/PNG/TIFF/WebP
 * - 流れ: autoOrient → sRGB変換 → TrueColor(RGB)固定 → ICC/EXIF/IPTC/XMP削除 → strip → 書き出し
 * - PNG: 不要チャンク（iCCP,sRGB,gAMA,pHYs,tEXt,zTXt,iTXt）を抑制
 * - ログ: ブラウザ表示 + 出力先に exif_delete_log.txt 追記保存
 */

ini_set("max_execution_time", 0);
ini_set("memory_limit", "-1");

// ---- 画面表示ヘッダ ----
ob_implicit_flush(true);
if (ob_get_level()) { @ob_end_flush(); }

echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>EXIF削除処理中</title><style>#status{font-size:20px;color:blue}</style></head><body>";
echo "<div id='status'>EXIF削除処理中...</div>";
flush();

// ---- 設定 ----
$HOME_DIR = "C:/xampp/htdocs/working/01_check_pre/";
$OUT_DIR  = "C:/xampp/htdocs/working/01_画像メタ情報_削除結果/";

$IMAGE_EXTS = ['jpg','jpeg','png','tif','tiff','webp'];
$DEFAULT_JPEG_QUALITY = 90;
$PNG_EXCLUDE_CHUNKS = 'iCCP,sRGB,gAMA,pHYs,tEXt,zTXt,iTXt';

// 電子/WEB前提：sRGB化＆RGB固定（ICCは残さない）
$FORCE_SRGB = true;
$FORCE_RGB  = true;

// ---- ログ ----
$LOG_PATH = rtrim($OUT_DIR, "/\\") . DIRECTORY_SEPARATOR . "exif_delete_log.txt";
$logBuf = [];
$startTime = microtime(true);

function log_line(&$buf, $line) {
    $ts = date('Y-m-d H:i:s');
    $buf[] = "[$ts] $line";
    echo htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "<br />\n";
    flush();
}
function ensure_dir($dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) throw new RuntimeException("フォルダ作成失敗: $dir");
    }
}
function is_image_ext($path, $exts) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, $exts, true);
}

// ---- 単一フレーム処理 ----
function process_single_frame(Imagick $img, string $ext, int $DEFAULT_JPEG_QUALITY, string $PNG_EXCLUDE_CHUNKS, bool $FORCE_SRGB, bool $FORCE_RGB) {
    // 1) 向き補正
//    $img->autoOrientImage();
    safe_auto_orient($img);


    // 2) sRGB 変換（常時）
    if ($FORCE_SRGB) {
        try { $img->transformImageColorspace(Imagick::COLORSPACE_SRGB); } catch (Throwable $e) {}
    }

    // 3) 強制RGB（L/CMYK/Indexed等もTrueColorに）
    if ($FORCE_RGB) {
        try { $img->setImageType(Imagick::IMGTYPE_TRUECOLOR); } catch (Throwable $e) {}
    }

    // 4) プロファイル類を明示削除 → 仕上げに strip
    foreach (['icc','icm','exif','iptc','xmp'] as $profile) {
        try { $img->removeImageProfile($profile); } catch (Throwable $e) {}
    }
    $img->stripImage(); // コメント等も含め全除去

    // 5) 出力形式維持
    $format = strtolower($img->getImageFormat());
    $img->setImageFormat($format);

    // 6) 形式別調整
    switch ($format) {
        case 'jpeg':
        case 'jpg':
            $q = (int)$img->getImageCompressionQuality();
            if ($q <= 0 || $q > 100) $q = $DEFAULT_JPEG_QUALITY;
            $img->setImageCompressionQuality($q);
            $img->setInterlaceScheme(Imagick::INTERLACE_NO);
            break;
        case 'png':
            $img->setOption('png:exclude-chunk', $PNG_EXCLUDE_CHUNKS);
            $img->setInterlaceScheme(Imagick::INTERLACE_NO);
            break;
        case 'tiff':
        case 'tif':
            // 既定任せ
            break;
        case 'webp':
            // 元状態尊重
            break;
        default:
            break;
    }
}

// ---- 画像処理本体 ----
function process_image($src_path, $dst_path, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB) {
    $img = new Imagick();
    $img->readImage($src_path);

    if ($img->getNumberImages() > 1) {
        $img = $img->coalesceImages();
        $processed = new Imagick();
        foreach ($img as $frame) {
            process_single_frame($frame, strtolower(pathinfo($src_path, PATHINFO_EXTENSION)), $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB);
            $processed->addImage($frame);
            $processed->setImageFormat($frame->getImageFormat());
        }
        $processed->writeImages($dst_path, true);
        $processed->destroy();
        $img->destroy();
        return;
    }

    process_single_frame($img, strtolower(pathinfo($src_path, PATHINFO_EXTENSION)), $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB);
    $img->writeImage($dst_path);
    $img->destroy();
}

// ---- 再帰処理 ----
function copy_and_remove_exif($src_dir, $dst_dir, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB, &$log, &$counters) {
    ensure_dir($dst_dir);
    $items = scandir($src_dir);
    foreach ($items as $item) {
        if ($item === "." || $item === "..") continue;

        $src_path = $src_dir . DIRECTORY_SEPARATOR . $item;
        $dst_path = $dst_dir . DIRECTORY_SEPARATOR . $item;

        if (preg_match('/^(Thumbs\.db|\.DS_Store)$/i', $item)) {
            log_line($log, "SKIP: $src_path");
            $counters['skipped']++;
            continue;
        }

        if (is_dir($src_path)) {
            copy_and_remove_exif($src_path, $dst_path, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB, $log, $counters);
            @touch($dst_path, filemtime($src_path));
            continue;
        }

        try {
            if (is_image_ext($src_path, $IMAGE_EXTS)) {
                try {
                    process_image($src_path, $dst_path, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB);
                    $counters['images']++;
                    log_line($log, "IMG : $src_path → $dst_path");
                } catch (Throwable $eImg) {
                    if (!@copy($src_path, $dst_path)) {
                        throw new RuntimeException("画像処理失敗かつコピーも失敗: $src_path :: [" . get_class($eImg) . "] " . $eImg->getMessage());
                    }
                    $counters['images_fallback_copy']++;
                    log_line($log, "FALLBACK COPY IMG: $src_path → $dst_path :: [" . get_class($eImg) . "] " . $eImg->getMessage());
                }
            } else {
                ensure_dir(dirname($dst_path));
                if (!copy($src_path, $dst_path)) throw new RuntimeException("コピー失敗: $src_path");
                $counters['others']++;
                log_line($log, "COPY: $src_path → $dst_path");
            }
            @touch($dst_path, filemtime($src_path));
        } catch (Throwable $e) {
            $counters['errors']++;
            log_line($log, "ERROR: $src_path :: [" . get_class($e) . "] " . $e->getMessage());
        }
    }
}


function safe_auto_orient(Imagick $img) {
    // 新しい環境ならそのまま使う
    if (method_exists($img, 'autoOrientImage')) {
        try { $img->autoOrientImage(); return; } catch (Throwable $e) {}
    }
    // 古い環境向け：EXIF Orientation を手動適用
    try {
        $orient = $img->getImageOrientation();
        switch ($orient) {
            case Imagick::ORIENTATION_TOPLEFT: // 1 正常
                break;
            case Imagick::ORIENTATION_TOPRIGHT: // 2 水平反転
                $img->flopImage(); break;
            case Imagick::ORIENTATION_BOTTOMRIGHT: // 3 180度回転
                $img->rotateImage(new ImagickPixel(), 180); break;
            case Imagick::ORIENTATION_BOTTOMLEFT: // 4 垂直反転
                $img->flipImage(); break;
            case Imagick::ORIENTATION_LEFTTOP: // 5 転置
                $img->transposeImage(); break;
            case Imagick::ORIENTATION_RIGHTTOP: // 6 90度回転（時計回り）
                $img->rotateImage(new ImagickPixel(), 90); break;
            case Imagick::ORIENTATION_RIGHTBOTTOM: // 7 斜め反転
                $img->transverseImage(); break;
            case Imagick::ORIENTATION_LEFTBOTTOM: // 8 90度回転（反時計回り）
                $img->rotateImage(new ImagickPixel(), -90); break;
        }
        // 以降の書き出しでOrientationタグを残さない
        $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    } catch (Throwable $e) {
        // 失敗しても続行（向き未適用）
    }
}


// ---- 実行 ----
$counters = ['images'=>0, 'images_fallback_copy'=>0, 'others'=>0, 'skipped'=>0, 'errors'=>0];

try {
    ensure_dir($OUT_DIR);
    for ($i=0; $i<3; $i++) { sleep(1); echo "."; flush(); }

    log_line($logBuf, "=== 開始：$HOME_DIR → $OUT_DIR ===");
    log_line($logBuf, "設定: FORCE_SRGB=" . ($FORCE_SRGB ? "true" : "false") . ", FORCE_RGB=" . ($FORCE_RGB ? "true" : "false") . ", DEFAULT_JPEG_QUALITY={$DEFAULT_JPEG_QUALITY}");
    copy_and_remove_exif($HOME_DIR, $OUT_DIR, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $FORCE_RGB, $logBuf, $counters);
    log_line($logBuf, "=== 完了 ===");
} catch (Throwable $e) {
    $counters['errors']++;
    log_line($logBuf, "FATAL: [" . get_class($e) . "] " . $e->getMessage());
}

$elapsed = round(microtime(true) - $startTime, 2);
log_line($logBuf, "処理時間: {$elapsed}s / 画像: {$counters['images']} / フォールバックコピー: {$counters['images_fallback_copy']} / その他: {$counters['others']} / スキップ: {$counters['skipped']} / エラー: {$counters['errors']}");

try {
    ensure_dir($OUT_DIR);
    file_put_contents($LOG_PATH, implode(PHP_EOL, $logBuf) . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo "<p>ログ保存: " . htmlspecialchars($LOG_PATH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
} catch (Throwable $e) {
    echo "<p>ログ保存エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
}

echo "<br />＜====================　EXIF削除処理終了　====================＞<br />";
echo "</body></html>";


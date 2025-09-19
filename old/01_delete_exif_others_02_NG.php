<?php
/**
 * 画像メタ削除 + sRGB変換（デフォルトON）
 * - 対象: JPG / JPEG / PNG / TIFF / WebP（必要なら拡張可）
 * - 流れ: autoOrient -> （任意で sRGB 変換）-> strip（EXIF/IPTC/XMP/ICC/コメント等）-> 書き出し
 * - PNGは不要チャンク（iCCP, sRGB, gAMA, pHYs, tEXt, zTXt, iTXt）を抑制
 * - ログ: ブラウザ表示 + 出力先に exif_delete_log.txt 追記保存
 */

ini_set("max_execution_time", 0);
ini_set("memory_limit", "-1");

// ---- 画面表示：進捗ヘッダ ----
ob_implicit_flush(true);
if (ob_get_level()) { @ob_end_flush(); } // バッファ未開始時のE_NOTICE回避

echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>EXIF削除処理中</title><style>#status{font-size:20px;color:blue}</style></head><body>";
echo "<div id='status'>EXIF削除処理中...</div>";
flush();

// ---- 設定 ----
$HOME_DIR = "C:/xampp/htdocs/working/01_画像メタ情報delete/";
$OUT_DIR  = "C:/xampp/htdocs/working/01_画像メタ情報delete_result/";

// 画像拡張子
$IMAGE_EXTS = ['jpg','jpeg','png','tif','tiff','webp'];

// 既定JPEG品質（元品質が取得できない場合に使用）
$DEFAULT_JPEG_QUALITY = 90;

// PNGのメタデータ系チャンクを出力しない
$PNG_EXCLUDE_CHUNKS = 'iCCP,sRGB,gAMA,pHYs,tEXt,zTXt,iTXt';

// ★ sRGB変換フラグ（デフォルト指定）— 印刷は考えない前提なので既定ON
$FORCE_SRGB = true;

// ログ
$LOG_PATH = rtrim($OUT_DIR, "/\\") . DIRECTORY_SEPARATOR . "exif_delete_log.txt";
$logBuf = [];
$startTime = microtime(true);

// ---- ユーティリティ ----
function log_line(&$buf, $line) {
    $ts = date('Y-m-d H:i:s');
    $buf[] = "[$ts] $line";
    echo htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "<br />\n";
    flush();
}

function ensure_dir($dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            throw new RuntimeException("フォルダ作成に失敗: $dir");
        }
    }
}

function is_image_ext($path, $exts) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, $exts, true);
}

// ---- 単一フレームの画像処理 ----
function process_single_frame(Imagick $img, string $ext, int $DEFAULT_JPEG_QUALITY, string $PNG_EXCLUDE_CHUNKS, bool $FORCE_SRGB) {
    // 1) 向き補正
    $img->autoOrientImage();

    // 2) 必要なら sRGB 変換（ICCは埋め込まず、電子前提の簡易変換）
    if ($FORCE_SRGB) {
        try {
            $img->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        } catch (Throwable $e) {
            // 失敗しても続行
        }
    }

    // 3) メタ全除去（EXIF/IPTC/XMP/ICC/コメント）
    $img->stripImage();

    // 4) 出力形式の最終決定（読み取りフォーマットを保持）
    $format = strtolower($img->getImageFormat()); // jpeg/png/tiff/webp etc.
    $img->setImageFormat($format);

    // 5) フォーマット別の最終調整
    switch ($format) {
        case 'jpeg':
        case 'jpg':
            $q = (int)$img->getImageCompressionQuality();
            if ($q <= 0 || $q > 100) { $q = $DEFAULT_JPEG_QUALITY; }
            $img->setImageCompressionQuality($q);
            $img->setInterlaceScheme(Imagick::INTERLACE_NO);
            break;

        case 'png':
            // 不要チャンクを出さない（ICC等を再付与しない）
            $img->setOption('png:exclude-chunk', $PNG_EXCLUDE_CHUNKS);
            $img->setInterlaceScheme(Imagick::INTERLACE_NO);
            break;

        case 'tiff':
        case 'tif':
            // デフォルト任せ
            break;

        case 'webp':
            // ここでは品質は変更しない（元状態尊重）
            break;

        default:
            // 何もしない
            break;
    }
}

// ---- 画像処理 本体 ----
function process_image($src_path, $dst_path, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB) {
    $ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));

    // 読み込み（破損/非対応で例外ありうる）
    $img = new Imagick();
    $img->readImage($src_path);

    // マルチフレーム（TIFFなど）
    if ($img->getNumberImages() > 1) {
        $img = $img->coalesceImages();
        $processed = new Imagick();
        foreach ($img as $frame) {
            // 各フレームを個別処理
            process_single_frame($frame, $ext, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB);
            $processed->addImage($frame);
            $processed->setImageFormat($frame->getImageFormat());
        }
        // マルチフレーム書き出し
        $processed->writeImages($dst_path, true);
        $processed->destroy();
        $img->destroy();
        return;
    }

    // 単一フレーム
    process_single_frame($img, $ext, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB);
    $img->writeImage($dst_path);
    $img->destroy();
}

// ---- メイン再帰処理 ----
function copy_and_remove_exif($src_dir, $dst_dir, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, &$log, &$counters) {
    ensure_dir($dst_dir);

    $items = scandir($src_dir);
    foreach ($items as $item) {
        if ($item === "." || $item === "..") continue;

        $src_path = $src_dir . DIRECTORY_SEPARATOR . $item;
        $dst_path = $dst_dir . DIRECTORY_SEPARATOR . $item;

        // 隠しシステムファイル等はスキップ
        if (preg_match('/^(Thumbs\.db|\.DS_Store)$/i', $item)) {
            log_line($log, "SKIP: $src_path");
            $counters['skipped']++;
            continue;
        }

        if (is_dir($src_path)) {
            copy_and_remove_exif($src_path, $dst_path, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $log, $counters);
            // ディレクトリの更新日時継承
            @touch($dst_path, filemtime($src_path));
            continue;
        }

        try {
            if (is_image_ext($src_path, $IMAGE_EXTS)) {
                // 破損/非対応形式に備えて個別try
                try {
                    process_image($src_path, $dst_path, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB);
                    $counters['images']++;
                    log_line($log, "IMG : $src_path → $dst_path");
                } catch (Throwable $eImg) {
                    // 読み込みや書き出し失敗：フォールバック（コピー）
                    if (!@copy($src_path, $dst_path)) {
                        throw new RuntimeException("画像処理失敗かつコピーも失敗: $src_path :: [" . get_class($eImg) . "] " . $eImg->getMessage());
                    }
                    $counters['images_fallback_copy']++;
                    log_line($log, "FALLBACK COPY IMG: $src_path → $dst_path :: [" . get_class($eImg) . "] " . $eImg->getMessage());
                }
            } else {
                // 非画像はそのままコピー
                ensure_dir(dirname($dst_path));
                if (!copy($src_path, $dst_path)) {
                    throw new RuntimeException("コピーに失敗: $src_path");
                }
                $counters['others']++;
                log_line($log, "COPY: $src_path → $dst_path");
            }

            // 更新日時を継承
            @touch($dst_path, filemtime($src_path));
        } catch (Throwable $e) {
            $counters['errors']++;
            log_line($log, "ERROR: $src_path :: [" . get_class($e) . "] " . $e->getMessage());
        }
    }
}

// ---- 実行 ----
$counters = [
    'images' => 0,
    'images_fallback_copy' => 0,
    'others' => 0,
    'skipped' => 0,
    'errors' => 0
];

try {
    ensure_dir($OUT_DIR);

    // 簡単なプログレス演出
    for ($i=0; $i<3; $i++) { sleep(1); echo "."; flush(); }

    log_line($logBuf, "=== 開始：$HOME_DIR → $OUT_DIR ===");
    log_line($logBuf, "設定: FORCE_SRGB=" . ($FORCE_SRGB ? "true" : "false") . ", DEFAULT_JPEG_QUALITY={$DEFAULT_JPEG_QUALITY}");
    copy_and_remove_exif($HOME_DIR, $OUT_DIR, $IMAGE_EXTS, $DEFAULT_JPEG_QUALITY, $PNG_EXCLUDE_CHUNKS, $FORCE_SRGB, $logBuf, $counters);
    log_line($logBuf, "=== 完了 ===");
} catch (Throwable $e) {
    $counters['errors']++;
    log_line($logBuf, "FATAL: [" . get_class($e) . "] " . $e->getMessage());
}

$elapsed = round(microtime(true) - $startTime, 2);
log_line($logBuf, "処理時間: {$elapsed}s / 画像: {$counters['images']} / フォールバックコピー: {$counters['images_fallback_copy']} / その他: {$counters['others']} / スキップ: {$counters['skipped']} / エラー: {$counters['errors']}");

// ログ保存
try {
    ensure_dir($OUT_DIR);
    file_put_contents($LOG_PATH, implode(PHP_EOL, $logBuf) . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo "<p>ログ保存: " . htmlspecialchars($LOG_PATH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
} catch (Throwable $e) {
    echo "<p>ログ保存エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
}

echo "<br />＜====================　EXIF削除処理終了　====================＞<br />";
echo "</body></html>";

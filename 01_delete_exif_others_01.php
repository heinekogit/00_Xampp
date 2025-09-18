<?php
ini_set("max_execution_time",0);

ob_implicit_flush(true);
ob_end_flush();

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>EXIF削除処理中</title>";
echo "<style>
    #status {
        font-size: 20px;
        color: blue;
    }
</style>";
echo "</head>";
echo "<body>";
echo "<div id='status'>EXIF削除処理中...</div>";
flush();

for ($i = 0; $i < 3; $i++) {
    sleep(1);
    echo ".";
    flush();
}

$home_dir = "C:/xampp/htdocs/working/01_画像メタ情報delete/";
$out_dir = "C:/xampp/htdocs/working/01_画像メタ情報delete_result/";

function copy_and_remove_exif($src_dir, $dst_dir) {
    if (!is_dir($dst_dir)) {
        mkdir($dst_dir, 0777, true);
    }
    $items = scandir($src_dir);
    foreach ($items as $item) {
        if ($item == "." || $item == "..") continue;
        $src_path = $src_dir . "/" . $item;
        $dst_path = $dst_dir . "/" . $item;
        if (is_dir($src_path)) {
            copy_and_remove_exif($src_path, $dst_path);
        } else {
            // 画像ファイルのみ処理（拡張子判定）
            $ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'tif', 'tiff'])) {
                $im = new Imagick($src_path);
                foreach (['exif', 'iptc', 'xmp'] as $profile) {
                    try {
                        $im->removeImageProfile($profile);
                    } catch (ImagickException $e) {
                        // プロファイルが無い場合は何もしない
                    }
                }
                $format = strtolower($im->getImageFormat());
                $im->setImageFormat($format);
                $f = fopen($dst_path, "wb");
                $im->writeImageFile($f);
                fclose($f);
                $im->destroy();
            } else {
                // 画像以外はそのままコピー
                copy($src_path, $dst_path);
            }
        }
    }
}

copy_and_remove_exif($home_dir, $out_dir);

echo "<br />＜====================　EXIF削除処理終了　====================＞<br />";
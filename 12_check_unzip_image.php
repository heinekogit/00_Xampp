<?php

ini_set("max_execution_time", 0);
ini_set('memory_limit', '512M');

// EPUB用XHTMLフォルダのパス
$home_dir = "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/";

// ログファイルの用意
$filename = 'C:/xampp/htdocs/working/10_output_logs/epub_image_checklog.txt';
if (!file_exists($filename)) {
    touch($filename);
}

// ホームフォルダの処理
if ($h_dh = opendir($home_dir)) {
    while (($title_folder = readdir($h_dh)) !== false) {
        if ($title_folder != "." && $title_folder != "..") {
            $title_dir = $home_dir . $title_folder;

            // タイトルフォルダ内の処理
            if (is_dir($title_dir)) {
                echo "<br />タイトルフォルダ：" . $title_folder . "　=============================<br />";
                $log_content = "タイトルフォルダ：" . $title_folder . "　=============================\r\n";

                if ($t_dh = opendir($title_dir)) {
                    while (($chapter_folder = readdir($t_dh)) !== false) {
                        if ($chapter_folder != "." && $chapter_folder != "..") {
                            $chapter_dir = $title_dir . "/" . $chapter_folder;

                            // 話フォルダ内の処理
                            if (is_dir($chapter_dir)) {
                                $image_dir = $chapter_dir . "/item/image"; // item/imageフォルダを指定
                                if (is_dir($image_dir)) {
                                    echo "<br />話フォルダ：" . $chapter_folder . "　---------------------------------------------------------------------<br />";
                                    $log_content .= "話フォルダ：" . $chapter_folder . "　---------------------------------------------------------------------\r\n";

                                    $image_files = glob($image_dir . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE);

                                    if (!empty($image_files)) {
                                        $file_numbers = [];
                                        foreach ($image_files as $file_path) {
                                            $file_name = basename($file_path);

                                            // 画像情報の取得
                                            $image_size = getimagesize($file_path);
                                            $width = $image_size[0];
                                            $height = $image_size[1];
                                            $mime = $image_size['mime'];

                                            // 解像度の取得
                                            $dpi_x = $dpi_y = "不明";
                                            $image_resource = null;

                                            // ファイルタイプに応じて画像リソースを作成
                                            switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
                                                case 'jpg':
                                                case 'jpeg':
                                                    $image_resource = imagecreatefromjpeg($file_path);
                                                    break;
                                                case 'png':
                                                    $image_resource = imagecreatefrompng($file_path);
                                                    break;
                                                case 'gif':
                                                    $image_resource = imagecreatefromgif($file_path);
                                                    break;
                                            }

                                            if ($image_resource) {
                                                $resolution = imageresolution($image_resource);
                                                if (!empty($resolution)) {
                                                    $dpi_x = $resolution[0];
                                                    $dpi_y = $resolution[1];
                                                }
                                                imagedestroy($image_resource); // メモリ解放
                                            }

                                            echo "ファイル：" . $file_name . "　幅：" . $width . "　高さ：" . $height . "　種別：" . $mime . "　解像度：" . $dpi_x . "x" . $dpi_y . "<br />";
                                            $log_content .= "ファイル：" . $file_name . "　幅：" . $width . "　高さ：" . $height . "　種別：" . $mime . "　解像度：" . $dpi_x . "x" . $dpi_y . "\r\n";

                                            // ファイル名から番号を抽出（連番チェック用）
                                            if (preg_match('/(\d+)\./', $file_name, $matches)) {
                                                $file_numbers[] = (int)$matches[1];
                                            }
                                        }

                                        // 連番チェック
                                        if (!empty($file_numbers)) {
                                            sort($file_numbers);
                                            $min_number = min($file_numbers);
                                            $max_number = max($file_numbers);
                                            $expected_numbers = range($min_number, $max_number);
                                            $missing_numbers = array_diff($expected_numbers, $file_numbers);

                                            if (!empty($missing_numbers)) {
                                                $missing_numbers_formatted = implode(', ', array_map(function ($num) {
                                                    return sprintf('%03d', $num); // 3桁表記に変換
                                                }, $missing_numbers));
                                                echo "欠けている番号：" . $missing_numbers_formatted . "<br />";
                                                $log_content .= "欠けている番号：" . $missing_numbers_formatted . "\r\n";
                                            } else {
                                                echo "欠けている番号はありません。<br />";
                                                $log_content .= "欠けている番号はありません。\r\n";
                                            }
                                        } else {
                                            echo "連番形式のファイルが見つかりませんでした。<br />";
                                            $log_content .= "連番形式のファイルが見つかりませんでした。\r\n";
                                        }
                                    } else {
                                        echo "画像ファイルが見つかりませんでした。<br />";
                                        $log_content .= "画像ファイルが見つかりませんでした。\r\n";
                                    }
                                } else {
                                    echo "item/imageフォルダが見つかりませんでした。<br />";
                                    $log_content .= "item/imageフォルダが見つかりませんでした。\r\n";
                                }
                            }
                        }
                    }
                    closedir($t_dh);
                }

                // ログファイルに記録
                file_put_contents($filename, $log_content, FILE_APPEND);
            }
        }
    }
    closedir($h_dh);
}

echo "<br />＜====================　チェック終了　====================＞<br />";

?>


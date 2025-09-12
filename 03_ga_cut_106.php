<?php

// 20240830	RTOON仕様のサイズ 1600×2560の出力に合わせ、
//				先の700×1000の数字部分だけ書き換えアップデート。
//				フォームを使ったサイズ指定式に書き換えを予定の暫定置羅。

ini_set("max_execution_time",0);

ob_implicit_flush(true);
ob_end_flush();

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>処理中</title>";
echo "<style>
    #status {
        font-size: 20px;
        color: blue;
    }
</style>";
echo "</head>";
echo "<body>";
echo "<div id='status'>処理中...</div>";
flush();

for ($i = 0; $i < 5; $i++) {
    sleep(1);
    echo ".";
    flush();
}

$form_hriz_size = isset($_GET["horizontal_size"]) ? $_GET["horizontal_size"] : null;
$form_hriz_size_input = isset($_GET["horizontal_size_input"]) ? $_GET["horizontal_size_input"] : null;

$form_vert_size = isset($_GET["vertical_size"]) ? $_GET["vertical_size"] : null;
$form_vert_size_input = isset($_GET["vertical_size_input"]) ? $_GET["vertical_size_input"] : null;

$fm_hriz = $form_hriz_size !== null ? $form_hriz_size : $form_hriz_size_input;
$fm_vert = $form_vert_size !== null ? $form_vert_size : $form_vert_size_input;

$form_resolution = $_GET["resolution"];

$unitime = time();
dipslay_datetime($unitime, 'Asia/Tokyo');

function dipslay_datetime($unix_timestamp, $tz){
    date_default_timezone_set($tz);
    $script_tz = date_default_timezone_get();
    echo $script_tz;
    echo "<br />";
    echo date('Y年m月d日 H:i:s', $unix_timestamp);
    echo "<hr />";
}

echo "<br />";
echo "<b>作業内容：　03・画像カット分割作業</b>";
echo "<br /><br />";

$home_dir = "C:/xampp/htdocs/working/03_material_cut/";
$out_dir = "C:/xampp/htdocs/working/03_output_cut/";

$mae_nokori = "$out_dir/mae_nokori.jpg";
if (file_exists($mae_nokori)) {
    unlink($mae_nokori);
}

if ($h_dh = opendir($home_dir)) {
    $title_folder_list = array();
    $title_folder_num = 0;

    while (($title_folder = readdir($h_dh)) !== false) {
        if ($title_folder != "." && $title_folder != "..") {
            array_push($title_folder_list, $title_folder);
            echo "<br />タイトルフォルダ " . ($title_folder_num + 1) . "：" . $title_folder_list[$title_folder_num] ;
            echo "　=============================<br />";

            mkdir($out_dir . $title_folder_list[$title_folder_num], 0777);
            $out_title_folder = $out_dir . $title_folder_list[$title_folder_num];

            $title_dir = $home_dir . $title_folder_list[$title_folder_num] . "/";

            if ($t_dh = opendir($title_dir)) {
                $wa_folder_list = array();
                $wa_folder_num = 0;

                while (($wa_folder = readdir($t_dh)) !== false) {
                    if ($wa_folder != "." && $wa_folder != "..") {
                        array_push($wa_folder_list, $wa_folder);
                        echo "話フォルダ " . ($wa_folder_num + 1) . "：" . $wa_folder_list[$wa_folder_num] . "<br />";

                        $output_wa_dir = $out_title_folder . "/" . $wa_folder_list[$wa_folder_num];
                        mkdir($output_wa_dir, 0777);

                        $was_dir = $title_dir . $wa_folder_list[$wa_folder_num];

                        if ($wa_dir = opendir($was_dir)) {
                            $file_list = array();
                            $file_num = 0;

                            while (($file = readdir($wa_dir)) !== false) {
                                if ($file != "." && $file != "..") {
                                    $trgt_path = $was_dir . "/" . $file;
                                    $file_list[$file_num] = $trgt_path;
                                    $file_num++;
                                }
                            }

                            $ga_file_num = 0;
                            $global_file_num = 1;

                            foreach ($file_list as $path) {
                                echo "元画像： " . $file_list[$ga_file_num] . "<br />";
                                list($original_w, $original_h, $type, $attr) = getimagesize($file_list[$ga_file_num]);
                                $baseImage = new Imagick($path);
                                $next_w = $fm_hriz;
                                $hiritu = $next_w / $original_w;
                                $next_h = round($original_h * $hiritu);
                                echo "nextの高さは" . $next_h . "<br />";
                                $baseImage->resizeImage($next_w, $next_h, Imagick::FILTER_LANCZOS, 1);

                                $mae_nokori = "$out_dir/mae_nokori.jpg";

                                if (file_exists($mae_nokori)) {
                                    $mae_nokori_inst = new Imagick($mae_nokori);
                                    list($mae_nokori_w, $mae_nokori_h, $type, $attr) = getimagesize($mae_nokori);
                                    echo "前残り画像高：" . $mae_nokori_h . "<br />";
                                    $mae_plus_new_h = $mae_nokori_h + $next_h;
                                    echo "連続した高さは" . $mae_plus_new_h . "<br />";

                                    $mae_plus_new = new Imagick();
                                    $mae_plus_new->addImage($mae_nokori_inst);
                                    $mae_plus_new->addImage($baseImage);
                                    $mae_plus_new->resetIterator();

                                    $src = $mae_plus_new->appendImages(true);
                                    $forcut_height = $mae_plus_new_h;
                                } else {
                                    $src = $baseImage;
                                    $forcut_height = $next_h;
                                }

                                $cut_height = $fm_vert;
                                echo "元画像の高さは". $forcut_height . "<br />";
                                echo "カットは". $cut_height . "<br />";

                                $cut_num = 1;
                                $cut_position = 0;

                                while ($cut_position + $cut_height <= floor($forcut_height)) {
                                    $dest = clone $src;
                                    $dest->cropImage($fm_hriz, $cut_height, 0, $cut_position);
                                    $dest->setImageFormat("jpeg");
                                    $dest->setImageResolution($form_resolution, $form_resolution);

                                    $output_dest = $output_wa_dir . "/image-out_" . sprintf('%03d', $global_file_num) . ".jpg";
                                    $f = fopen($output_dest, "wb");
                                    $dest->writeImageFile($f);

                                    $cut_position += $cut_height;
                                    $cut_num++;
                                    $global_file_num++;
                                    echo "カットの座標は". $cut_position . "<br />";
                                }

                                $nokori_h = round($forcut_height - $cut_position);
                                echo "余ったのは " . $nokori_h . "<br />";

                                if ($nokori_h > 0.9) {
                                    if ($path !== end($file_list)) {
                                        $nokori_dest = clone $src;
                                        $nokori_dest->cropImage($fm_hriz, $nokori_h, 0, $cut_position);
                                        $nokori_dest->setImageResolution($form_resolution, $form_resolution);
                                        $nokori_dest->setImageFormat("jpeg");

                                        $output_nokori_dest = "$out_dir/mae_nokori.jpg";
                                        $f = fopen($output_nokori_dest, "wb");
                                        $nokori_dest->writeImageFile($f);
                                    } else {
                                        $final_dest = clone $src;
                                        $final_dest->cropImage($fm_hriz, $nokori_h, 0, $cut_position);
                                        $final_dest->setImageResolution($form_resolution, $form_resolution);
                                        $final_dest->setImageFormat("jpeg");

                                        $output_nokori_dest = $output_wa_dir . "/image-out_" . sprintf('%03d', $global_file_num) . ".jpg";
                                        $f = fopen($output_nokori_dest, "wb");
                                        $final_dest->writeImageFile($f);
                                        $global_file_num++;

                                        if (file_exists($mae_nokori)) {
                                            unlink($mae_nokori);
                                            echo "$mae_nokori を削除しました。<br /><br />";
                                        }
                                    }
                                } else {
                                    if (file_exists($mae_nokori)) {
                                        unlink($mae_nokori);
                                        echo "微小な前残り画像（$nokori_h px）を破棄<br />";
                                    }
                                }

                                $cut_position = 0;
                                $ga_file_num++;
                            }
                        }
                        $wa_folder_num++;
                    }
                }
            }
            $title_folder_num++;
        }
    }
    closedir($h_dh);
}

echo "<br />＜====================　処理終了　====================＞<br />";
?>

<?php

//	glob参考：https://magazine.techacademy.jp/magazine/39376
//	glob 名前のみ：https://magazine.techacademy.jp/magazine/40769

//	未装備
//	フォルダもコピー
//	https://qiita.com/r-fuji/items/4736d9598a215b289dec
//	https://thousand-tech.blog/php/php%E3%83%87%E3%82%A3%E3%83%AC%E3%82%AF%E3%83%88%E3%83%AA%E3%81%94%E3%81%A8%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB%E3%82%92%E3%82%B3%E3%83%94%E3%83%BC/


//	=================================================================================

$hand_dir = "C:/xampp/htdocs/working/11_distribute/01_shot/";
$post_dir = "C:/xampp/htdocs/working/11_distribute/02_posts/";

// 取得するファイルリストを生成
if ($h_dh = opendir($hand_dir)) {
    $shot_files_list = array();

    while (($shot_files = readdir($h_dh)) !== false) {
        if ($shot_files != "." && $shot_files != "..") {
            array_push($shot_files_list, $shot_files);
        }
    }
    closedir($h_dh);

    // 確認用の出力
    foreach ($shot_files_list as $file) {
        echo "(配列）コピー用ファイル名：" . $file . "</br>";
    }
}

echo "</br>=======================</br></br>";

// 配布先フォルダの確認とコピー処理
if ($p_dh = opendir($post_dir)) {
    $goal_folders_list = array();

    while (($title_folder = readdir($p_dh)) !== false) {
        if ($title_folder != "." && $title_folder != "..") {
            $title_path = $post_dir . $title_folder;

            if (is_dir($title_path)) {
                // タイトルフォルダ内の子フォルダを探す
                if ($sub_dh = opendir($title_path)) {
                    while (($child_folder = readdir($sub_dh)) !== false) {
                        if ($child_folder != "." && $child_folder != "..") {

                            $child_folder_path = $title_path . "/" . $child_folder;
                            if (is_dir($child_folder_path)) {

                                array_push($goal_folders_list, $child_folder_path);
                                echo "配布フォルダ名：" . $child_folder_path . "　-------------------------------------</br>";

                                // コピー処理
                                foreach ($shot_files_list as $shot_file) {
                                    $target = $hand_dir . $shot_file; // コピー元
                                    $mark = $child_folder_path . "/" . $shot_file; // コピー先
                                    
                                    echo "コピー先：" . $mark . "</br>";

                                    if (file_exists($target)) {
                                        $act = copy($target, $mark);
                                        if ($act) {
                                            echo "コピー終了：" . $shot_file . "</br>";
                                        } else {
                                            echo "コピー失敗：" . $shot_file . "</br>";
                                        }
                                    } else {
                                        echo "コピー元ファイルが存在しない：" . $target . "</br>";
                                    }
                                }
                            }
                        }
                    }
                    closedir($sub_dh);
                }
            }
        }
    }
    closedir($p_dh);
}


//	=================================================================================


?>
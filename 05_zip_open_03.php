<?php

ini_set("max_execution_time",0);		//	https://mrgoofy.hatenablog.com/entry/20100922/1285168658


//	ヘッダー表示	----------------------------------------------------------

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
echo "<b>作業内容：　05・ePub解凍とフォルダ構成チェック</b>";
echo "<br /><br />";				

//	ヘッダー表示終了	----------------------------------------------------------



//	echo "＜====================　処理開始　====================＞<br />";

//	処理フォルダ　=================================================================================

$home_dir = "C:/xampp/htdocs/working/05_check_epub_inner/01_zips/";		
$out_dir = "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/";		

    if ($h_dh = opendir($home_dir)) {

		$title_folder_list = array();
		$title_folder_num = 0;

        while (($title_folder = readdir($h_dh)) !== false) {						//タイトルフォルダsを取得

			if ($title_folder != "." && $title_folder != "..") {				

	            array_push($title_folder_list, $title_folder);
				echo "<br />タイトルフォルダ " . $title_folder_num + 1 . "：" . $title_folder_list[$title_folder_num] ;
				echo "　=================================================<br />";				

				mkdir($out_dir . $title_folder_list[$title_folder_num], 0777);		//カット画像収納フォルダを作成
				$out_title_folder = $out_dir . $title_folder_list[$title_folder_num];

				$title_dir = $home_dir . $title_folder_list[$title_folder_num] . "/";	//タイトルのディレクトリ


//	タイトルフォルダ内	========================================================================

    if ($t_dh = opendir($title_dir)) {

		$epub_list = array();
		$epub_file_num = 0;

        while (($epub_file = readdir($t_dh)) !== false) {							//タイトルフォルダ（180ガール）内

			if ($epub_file != "." && $epub_file != "..") {	
				
	            array_push($epub_list, $epub_file);
				echo "<br />";
				echo "ePubファイル " . $epub_file_num + 1 . "：" . $epub_list[$epub_file_num] ;
				echo "　-----------------------------------------<br />";
				$filename = pathinfo($epub_list[$epub_file_num], PATHINFO_FILENAME);	//	取得したepubファイル名の拡張子を削除
//				echo "フォルダ名は" . $filename . "<br />";

				$kaitou_epub_dir = $out_title_folder . "/" . $filename;
				mkdir($kaitou_epub_dir, 0777);								//カット画像収納フォルダを作成


		//	移植・ePub解凍	------------------------------------------------------------------------------------

//			echo "ターゲット：" . "C:/xampp/htdocs/working/05_check_epub_inner/01_zips/$title_folder_list[$title_folder_num]/$epub_list[$epub_file_num]" . "<br />";

			$zip = new ZipArchive;

//			echo $title_dir . $epub_list[$epub_file_num] . "<br />";

			if ($zip->open($title_dir . $epub_list[$epub_file_num]) === TRUE)
				 {
					$zip->extractTo($kaitou_epub_dir);
					$zip->close();
//					echo "解凍先：" . "C:/xampp/htdocs/working/05_check_epub_inner/02_xhtmls/$title_folder_list[$title_folder_num]/$filename/" . "<br />";
					echo "フォルダ構成：" . "<br />";

					$list1 = glob($kaitou_epub_dir . '/*', GLOB_ONLYDIR);

					foreach ($list1 as $disclose1) {
						print($disclose1);
						echo nl2br("\n");
						
					//	サブディレクトリのチェックを入れる	------------------------					
						$sub_dir =  glob($disclose1 . '/*', GLOB_ONLYDIR);
					
						foreach ($sub_dir as $sub) {
							print($sub);
							echo nl2br("\n");
						}	
					//	-----------------------------------------------------------
					}
					


				} else {
				echo '取得不可' . "<br /><br />";
			}				

			//	-----------------------------------------------------------------------------------------------------	
		
				$epub_file_num ++;
			}
		}
    closedir($t_dh);			//	タイトルフォルダ　終了	=========================================

				$title_folder_num ++;
				}
			}

        }
        closedir($h_dh);		//	ホームフォルダ　終了	=========================================
	}

		echo "<br />＜====================　処理終了　====================＞<br />";


//	=================================================================================

//			include "specification.html";

?>
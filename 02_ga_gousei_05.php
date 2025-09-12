<?php

ini_set("max_execution_time",0);		//	https://mrgoofy.hatenablog.com/entry/20100922/1285168658


//	処理中です画面の表示	----------------------------------------------------------
// 出力バッファリングを無効にする
ob_implicit_flush(true);
ob_end_flush();

// HTMLヘッダーを出力
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

// 「処理中」のメッセージを表示
echo "<div id='status'>処理中...</div>";
flush(); // バッファをフラッシュして、即座にブラウザに出力

// 長時間かかる処理をシミュレート
for ($i = 0; $i < 5; $i++) {
    sleep(1); // 実際の処理に置き換えてください
    echo "."; // 処理の進行状況を示すためにドットを表示
    flush();
}


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
echo "<b>作業内容：　02・画像連結作業</b>";
echo "<br /><br />";				

//	ヘッダー表示終了	----------------------------------------------------------



//	echo "＜====================　処理開始　====================＞<br />";

//	処理フォルダ　=================================================================================

	$home_dir = "C:/xampp/htdocs/working/02_material_gousei/";		
	$out_dir = "C:/xampp/htdocs/working/02_output_gousei/";		
	
    if ($h_dh = opendir($home_dir)) {

		$title_folder_list = array();
		$title_folder_num = 0;

        while (($title_folder = readdir($h_dh)) !== false) {						//タイトルフォルダsを取得

			if ($title_folder != "." && $title_folder != "..") {				

	            array_push($title_folder_list, $title_folder);
				echo "<br />タイトルフォルダ " . $title_folder_num + 1 . "：" . $title_folder_list[$title_folder_num] ;
				echo "　=============================<br />";				

				mkdir($out_dir . $title_folder_list[$title_folder_num], 0777);		//カット画像収納フォルダを作成
				$out_title_folder = $out_dir . $title_folder_list[$title_folder_num];

				$title_dir = $home_dir . $title_folder_list[$title_folder_num] . "/";	//タイトルのディレクトリ


//	タイトルフォルダ内	========================================================================

    if ($t_dh = opendir($title_dir)) {

		$wa_folder_list = array();
		$wa_folder_num = 0;

        while (($wa_folder = readdir($t_dh)) !== false) {							//タイトルフォルダ（180ガール）内

			if ($wa_folder != "." && $wa_folder != "..") {				

	            array_push($wa_folder_list, $wa_folder);
				echo "話フォルダ " . $wa_folder_num + 1 . "：" . $wa_folder_list[$wa_folder_num] . "<br />";

				$output_wa_dir = $out_title_folder . "/" . $wa_folder_list[$wa_folder_num];
				mkdir($output_wa_dir, 0777);								//カット画像収納フォルダを作成

				$was_dir = $title_dir . $wa_folder_list[$wa_folder_num];		//話のディレクトリ



//	話フォルダ内	========================================================================

		if ($wa_dir = opendir($was_dir)) {				
//			echo "話フォルダ：" . $was_dir. "<br />";			//確認用

				$file_list = array();
				$file_num = 0;

				while (($file = readdir($wa_dir)) !== false) {

					if ($file != "." && $file != "..") {				
								
						$trgt_path = $was_dir . "/" . $file;           
	        			$file_list[$file_num] = $trgt_path;				//画像ファイルの配列化

						$file_num ++;
					}
			
				}
								
//	画像ファイルごとの処理	==================================================================

//	$images = $file_list;							//古い処理。念のため残し。

//	$count = count($file_list);						//画像枚数を数え。確認用。使わない。
//	echo "画像枚数は" . $count . "<br />";

	$kotei_w = 700;					// アウトプット画像の幅
	$image_file_num = 1;			// アウトプット画像のナンバー数値
	$kasan_h = 0;					// 画像の積み上げ高

	$result = new Imagick();		// 30,000pix以下画像の台紙作成

	foreach ($file_list as $path) {	//	--------------------------------------------------

		$im = new Imagick($path);		//	コマ画像を作成

		$im->setImageCompressionQuality(80);		// ここで画質を80にしている。
								// https://www.php.net/manual/ja/imagick.setcompressionquality.php
								// https://www.php.net/manual/ja/imagick.setimagecompressionquality.php
		
	//	画像サイズの計算	-----------------------------
				$original_w = $im->getImageWidth();
		//			echo $im->getImageWidth() . "<br />";							//確認用
				$original_h = $im->getImageHeight();
		//			echo $im->getImageHeight() . "<br />";							//確認用

				$hiritu = $kotei_w/$original_w;										//縦横比率を計算し、		
				$henkan_h = $original_h * $hiritu;									//幅700に対応した縦長出し

				$henkan_h = round($henkan_h);										// 小数点以下の調整。フォトコンバイン合わせ
//				$henkan_h = ceil($henkan_h);											
//				echo "横700の高さは" . $henkan_h  . "<br />";
				
		//	画像サイズの計算　終了	------------------------
				
		$im->resizeImage($kotei_w, $henkan_h, Imagick::FILTER_LANCZOS, 1);			//	画像サイズ変更

//	解像度を取得しようとしてエラー
//	print_r(imageresolution($im));


		$result->addImage($im);
		$kasan_h += $henkan_h;

		if ($kasan_h > 25000) {

			$result->resetIterator();
			$combined = $result->appendImages(true);

			$combined->setImageResolution(72, 72);					//	解像度を72に。
//				print_r($combined->getImageResolution());

			$combined->setImageFormat("jpeg");
			$seikei_num = sprintf('%02d', $image_file_num); 	// 出力画像名の0埋め
			$output_combined = $output_wa_dir . "/" . $wa_folder_list[$wa_folder_num] . "_" . $seikei_num . ".jpg";

			$f=fopen($output_combined, "wb");					// php公式サイトのimagickのファイル出力が見当たらず。
			$combined->writeImageFile($f);					//ネットから。wbがよくわからないが動作はする。

			$combined->destroy();
			$result->destroy();
			$im->destroy();

			$kasan_h = 0;
			$image_file_num ++;

		} elseif ($path === end($file_list)) {
		
//			echo "末尾画像ファイルの高さは、" . $kasan_h. "<br />";		//末尾の確認用

			$result->resetIterator();
			$combined = $result->appendImages(true);

			$combined->setImageResolution(72, 72);					//	解像度を72に。

			$combined->setImageFormat("jpeg");
			$seikei_num = sprintf('%02d', $image_file_num); 	// 出力画像名の0埋め
			$output_combined = $output_wa_dir . "/" . $wa_folder_list[$wa_folder_num] . "_" . $seikei_num . ".jpg";
		
			$f=fopen($output_combined, "wbb");			// php公式サイトのimagickのファイル出力が見当たらず。
			$combined->writeImageFile($f);				//ネットから。wbがよくわからないが動作はする。
		
			$combined->destroy();
			$result->destroy();
			$im->destroy();

//			$kasan_h = 0;
//			$image_file_num = 1;

//			break;
			}

		}	//foreach画 終り	--------------------------------------------------------------
					//	https://www.php.net/manual/ja/imagick.appendimages.php
					// 配列終り：https://kakechimaru.com/php_foreach_first_last/
	
	}
	closedir($wa_dir);			//	話フォルダ内　終了	=========================================
						
		//	echo "配列の中身は<br />";
		//	print_r($file_list);
						
			$wa_folder_num ++;
						
			}
						
		}
						
    }
    closedir($t_dh);			//	タイトルフォルダ　終了	=========================================

				$title_folder_num ++;
				}
			}


        }
        closedir($h_dh);		//	ホームフォルダ　終了	=========================================


		echo "<br />＜====================　処理終了　====================＞<br />";


//	=================================================================================

//			include "specification.html";

?>
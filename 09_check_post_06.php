<?php

ini_set("max_execution_time",0);		//https://mrgoofy.hatenablog.com/entry/20100922/1285168658
ini_set('memory_limit', '512M');


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
echo "<b>作業内容：　04・作業後の画像データチェック</b>";
echo "<br /><br />";				

//	ヘッダー表示終了	----------------------------------------------------------


//	処理フォルダ　=================================================================================

	$home_dir = "C:/xampp/htdocs/working/09_check_post/";		
	$out_dir = "C:/xampp/htdocs/working/02_output_gousei/";		
	
    if ($h_dh = opendir($home_dir)) {

		$title_folder_list = array();
		$title_folder_num = 0;

        while (($title_folder = readdir($h_dh)) !== false) {						//タイトルフォルダsを取得

			if ($title_folder != "." && $title_folder != "..") {				

	            array_push($title_folder_list, $title_folder);
				echo "<br />タイトルフォルダ " . $title_folder_num + 1 . "：" . $title_folder_list[$title_folder_num] ;
				echo "　=============================<br />";				

				$title_dir = $home_dir . $title_folder_list[$title_folder_num] . "/";	//タイトルのディレクトリ


//	タイトルフォルダ内	========================================================================

    if ($t_dh = opendir($title_dir)) {

		$wa_folder_list = array();
		$wa_folder_num = 0;

        while (($wa_folder = readdir($t_dh)) !== false) {							//タイトルフォルダ（180ガール）内

			if ($wa_folder != "." && $wa_folder != "..") {				

	            array_push($wa_folder_list, $wa_folder);
				echo "<br />話フォルダ " . $wa_folder_num + 1 . "： " . $wa_folder_list[$wa_folder_num] . "　---------------------------------------------------------------------<br />";

				$was_dir = $title_dir . $wa_folder_list[$wa_folder_num];		//話のディレクトリ



//	話フォルダ内	========================================================================

		if ($wa_dir = opendir($was_dir)) {				
//			echo "<br />話フォルダ：" . $was_dir . "<br />";			//確認用

				$file_list = array();
				$file_num = 0;

				while (($file = readdir($wa_dir)) !== false) {

					if ($file != "." && $file != "..") {				
								
						$trgt_path = $was_dir . "/" . $file;           
	        			$file_list[$file_num] = $trgt_path;				//画像ファイルの配列化

						$resource = new Imagick($file_list[$file_num]);
						$imageResolution = $resource->getImageResolution();
//						var_dump(implode($imageResolution)) ;			// これはxy両方出る				
//						print_r($imageResolution['x']);					// これok
//						echo "<br />";


						$image_size = getimagesize($file_list[$file_num]);			//画像ファイルの数値（配列）を取得
//						var_export($image_size) ;
//						echo "<br />";
//						echo $file_list[$file_num] . "<br />";
//						echo $file . "<br />";
//						echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
//								"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
//						$kaizodo = imagecreatefromjpeg($file_list[$file_num]);	//解像度取得用のファイル取得
//						$kaizodo_info = imageresolution($kaizodo);				//解像度取得。タイプ検出とバッティング中 → 不正確。不使用へ
//						echo "　解像度：" . $kaizodo_info[0];
//						echo "<br />";

						if ($image_size[2] == "1") {		//	使えるが、$image_size['mime']がイキ。
							$type = "gif";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefromgif($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

//							$youryou = filesize($file_list[$file_num]);
//							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
//							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";
							echo "<br />";

						} elseif ($image_size[2] == "2") {
							$type = "jpeg";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefromjpeg($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

							$youryou = filesize($file_list[$file_num]);
							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";

						} elseif ($image_size[2] == "3") {
							$type = "png";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefrompng($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

//							$youryou = filesize($file_list[$file_num]);
//							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
//							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";

						} elseif ($image_size[2] == "5") {
							$type = "psd";							

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							echo "　解像度：" . $imageResolution['x'];

//							$youryou = filesize($file_list[$file_num]);
//							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
//							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";

						} elseif ($image_size[2] == "6") {
							$type = "bmp";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefrombmp($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

//							$youryou = filesize($file_list[$file_num]);
//							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
//							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";

						} elseif ($image_size[2] == "7") {
							$type = "tiff";							

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							echo "　解像度：" . $imageResolution['x'];

//							$youryou = filesize($file_list[$file_num]);
//							$file_size_kirobyte = $youryou / 1024;			// キロバイト表示に。				
//							echo "　サイズ：" . ceil($file_size_kirobyte) . " KB";
							echo "<br />";
						}
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

//	画像サイズの計算	-----------------------------
			$original_w = $im->getImageWidth();
	//			echo $im->getImageWidth() . "<br />";							//確認用
			$original_h = $im->getImageHeight();
	//			echo $im->getImageHeight() . "<br />";							//確認用

			$hiritu = $kotei_w/$original_w;										//縦横比率を計算し、		
			$henkan_h = $original_h * $hiritu;									//幅700に対応した縦長出し
	//			echo $henkan_h  . "<br />";
	//	画像サイズの計算　終了	------------------------
			
//	$im->resizeImage($kotei_w, $henkan_h, Imagick::FILTER_LANCZOS, 1);			//	画像サイズ変更
	$im->resizeImage($kotei_w, (int) round($henkan_h), Imagick::FILTER_LANCZOS, 1);		//20250129 GPTによるデスクトップのエラー回避反映。


	$result->addImage($im);
	$kasan_h += $henkan_h;

	//					$im = new Imagick($file_list[$file_num]);		//	解像度取得のテスト
	//					print_r(imageresolution($result));
	//					echo imageresolution($result);

//	解像度を取得しようとしてエラー
//	print_r(imageresolution($im));


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


		echo "<br />＜====================　チェック終了　====================＞<br />";


//	=================================================================================

//			include "specification.html";


//	ファイルに出力
//	https://www.php.net/manual/ja/function.file-put-contents.php


// 処理完了のメッセージを表示
echo "<script>document.getElementById('status').innerHTML = '処理完了';</script>";

// HTMLフッターを出力
echo "</body>";
echo "</html>";


?>

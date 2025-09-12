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
echo "<b>作業内容：　03・画像カット分割作業</b>";
echo "<br /><br />";				

//	ヘッダー表示終了	----------------------------------------------------------



//	処理フォルダ　=========================================================================

$home_dir = "C:/xampp/htdocs/working/03_material_cut/";		
$out_dir = "C:/xampp/htdocs/working/03_output_cut/";		
	
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

//	タイトルフォルダ内	===================================================================

//	$dir = "C:/xampp/htdocs/working/03_material_cut/detarame";					//過渡的のホームポジション・タイトルフォルダ

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


//	話フォルダ内 ========================================================================

			if ($wa_dir = opendir($was_dir)) {				
	//			echo "話フォルダ：" . $was_dir. "<br />";			//確認用
	
					$file_list = array();
					$file_num = 0;
	
					while (($file = readdir($wa_dir)) !== false) {
	
						if ($file != "." && $file != "..") {				
									
							$trgt_path = $was_dir . "/" . $file;           
//							$file_list[$file_num] = $trgt_path;				//画像ファイルの配列化
//							$file_num ++;


// 拡張子をチェックして画像ファイルのみ処理　20250124GPT
            $extension = pathinfo($trgt_path, PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'])) {
                $file_list[$file_num] = $trgt_path; // 画像ファイルの配列化
                $file_num++;
            } else {
                // 画像ファイルでない場合はスキップ（ログに記録など）
                echo "スキップされたファイル: " . $trgt_path . "<br />";
            }



						}				
			}

					
//	画像ファイル処理	=====================================================================

	$ga_file_num = 0;

foreach ($file_list as $path) {			//	単画ファイル処理	----------------------------------------

	echo "元画像： " . $file_list[$ga_file_num] . "<br />";

//	画像のリサイズ調整	-------------------------------------------------------------------------------
					//	getimagesize	
					//	https://www.php.net/manual/ja/function.getimagesize.php

	list($original_w, $original_h, $type, $attr) = getimagesize($file_list[$ga_file_num]);		//	素材画像の情報取得
					//	echo $original_h;

	$baseImage = new Imagick($path); 									// imagick版

	$next_w = 700;								//	変換後の幅サイズ

	$hiritu = $next_w/$original_w;				//	縮小比率＝ 変換後の幅 ÷ 現幅
//	$next_h = $original_h * $hiritu;			//	縮小比率 × 変換後の高さ
	$next_h = round($original_h * $hiritu); 	//	20250124、GPTにより四捨五入の処理加え
		echo "nextの高さは" . $next_h . "<br />";	

//	$baseImage->resizeImage($next_w, $next_h, Imagick::FILTER_LANCZOS, 1);			//	imgk、画像サイズ変更
	$baseImage->resizeImage((int)$next_w, (int)$next_h, Imagick::FILTER_LANCZOS, 1);	//250124、デスクトップのphp8.2.27で不具合、GPT調整


//	前画像の残りを次画像のアタマに挿入する	-------------------------------------------------------------------------------

	$mae_nokori = "C:/xampp/htdocs/working/03_output_cut/mae_nokori.jpg";		//	前残り画像位置。暫定

	if (file_exists($mae_nokori)) {												//	もし前残り画像があったら（次の画像と連結）					

		$mae_nokori_inst = new Imagick($mae_nokori); 								// 前残り画像を新しくimgk取り込み

		list($mae_nokori_w, $mae_nokori_h, $type, $attr) = getimagesize($mae_nokori);		//	前残り画像の情報取得
			echo "前残り画像高：" . $mae_nokori_h . "<br />";

		$mae_plus_new_h = $mae_nokori_h + $next_h;
			echo "連結した高さは" . $mae_plus_new_h . "<br />";

		$mae_plus_new = new Imagick();											// 前残り画像を足した高さの台紙を作成

		$mae_plus_new->addImage($mae_nokori_inst);								//	前残りを貼り込み
		$mae_plus_new->addImage($baseImage);									//	本体を貼り込み
		$mae_plus_new->resetIterator();

		$src = $mae_plus_new->appendImages(true);	//	カット用画像を、前残り＋本体画像のデータとする
		$forcut_height = $mae_plus_new_h;			//	カット用画像を、前残り画像込みの高さとする

	} else {										//	前残り画像がなかった場合（前画像と本体画像の連結はなし）

		$src = $baseImage;							//	カット用のソースは本体ママ
		$forcut_height = $next_h;					//	カット用画像を、前残りなし画像の高さとする

	}

//	画像のカット	---------------------------------------------------------------------------------------------------

	$cut_height = 1000;						//	カット高1000で定義
//		echo "元画像の高さは". $forcut_height . "<br />";
//		echo "カットは". $cut_height . "<br />";
	
	$cut_num = 1;			//	カットの枚数
	$cut_position = 0;		//	カット開始位置
	$end_line = $cut_position + 1000;	//

	$nokori_h = $forcut_height - $cut_position;							//	残り画像の高さを算出			

	while($cut_position + 1000 <= $forcut_height) {		//	元画像が残り1000以下になるまでカット、のループのスタート	---------------------------

		$seikei_cut_num = sprintf('%02d', $cut_num); 							// 0埋め
		$seikei_ga_file_num = sprintf('%02d', $ga_file_num + 1); 				// 0埋め

//		$dest = $src->clone();											//　オリジナルのクローンをカット
		$dest = clone $src;												//　上記が不具合（非推奨とのこと）20250124、GPT。
		$dest->cropImage(700, 1000, 0, $cut_position);					// 元画像のカット位置から1000を$destにコピー
//			echo $dest;																		https://www.php.net/manual/ja/imagick.cropimage.php

		$dest->setImageFormat("jpeg");
		$dest->setImageResolution(72, 72);					//	解像度を72に。

		$seikei_num = sprintf('%02d', $ga_file_num); 	// 出力画像名の0埋め
		$output_dest = $output_wa_dir . "/image-out" . "_" . $seikei_ga_file_num . "_" . $seikei_cut_num . ".jpg";
	
		$f = fopen($output_dest, "wbb");			// php公式サイトのimagickのファイル出力が見当たらず。
		$dest->writeImageFile($f);				//ネットから。wbがよくわからないが動作はする。

//		$dest->destroy();						//	必要。カット画像を一枚ずつ出力するようになる。→　そうでもなかった。

		$cut_num ++;
		$cut_position += 1000;
			echo "カットの座標は". $cut_position . "<br />";

		}										//	while(画像が1000以下になるまで)ループ終了	---------------------

		
		$nokori_h = $forcut_height - $cut_position;							//	残り画像の高さを算出			
			echo "余ったのは " . $nokori_h . "<br />";							//	サンプルは元画像高が8750
		

		if ($nokori_h > 0) {							//	0以上1000以下の画像残りがあるなら、前残り用画像を作成
		
			if ($path !== end($file_list)) {						//もし連番後ろの画像ファイルがあるなら
		
//				$nokori_dest = $src->clone();											//　オリジナルのクローンをカット
				$nokori_dest = clone $src;												//　20250124、上記不具合でGPT推奨の方式
				$nokori_dest->cropImage(700, $nokori_h, 0, $cut_position);					// 元画像のカット位置から1000を$destにコピー	


				$nokori_dest->setImageResolution(72, 72);					//	解像度を72に。
				$nokori_dest->setImageFormat("jpeg");
				$seikei_num = sprintf('%02d', $ga_file_num); 	// 出力画像名の0埋め

				$output_nokori_dest = $out_dir . "/mae_nokori.jpg";		//	前残し画像の出力先
			
				$f=fopen($output_nokori_dest, "wbb");			// php公式サイトのimagickのファイル出力が見当たらず。
				$nokori_dest->writeImageFile($f);				//ネットから。wbがよくわからないが動作はする。

			} else {												//もし画像ファイルがフォルダ内最後なら

//				$final_dest = $src->clone();											//　オリジナルのクローンをカット　→非推奨になった、とGPT
				// オリジナルの画像データをコピー　20250124にGPT推奨
				$final_dest = new Imagick();
				$final_dest->readImageBlob($src->getImageBlob());  // 画像の内容をコピー
				
				$final_dest->cropImage(700, $nokori_h, 0, $cut_position);					// 元画像のカット位置から1000を$destにコピー	

				$seikei_cut_num = "01";

				$final_dest->setImageResolution(72, 72);					//	解像度を72に。
				$final_dest->setImageFormat("jpeg");
				$seikei_num = sprintf('%02d', $ga_file_num); 	// 出力画像名の0埋め

				$output_nokori_dest = $output_wa_dir . "/image-out" . "_" . $seikei_ga_file_num + 1 . "_" . $seikei_cut_num . ".jpg";
			
				$f=fopen($output_nokori_dest, "wbb");			// php公式サイトのimagickのファイル出力が見当たらず。
				$final_dest->writeImageFile($f);				//ネットから。wbがよくわからないが動作はする。
				
				$delete_file =  "C:/xampp/htdocs/working/03_output_cut/mae_nokori.jpg";
				if (unlink($delete_file)){
					echo $delete_file . " を削除しました。<br /><br />";
				}else{
					echo $delete_file . " の削除に失敗しました<br />";						
				}

			}			

		} else {												//	画像残りがない場合

			$delete_file =  "C:/xampp/htdocs/working/03_output_cut/mae_nokori.jpg";
			if (unlink($delete_file)){
				echo $delete_file . " を削除しました。<br /><br />";
			}else{
				echo $delete_file . " の削除に失敗しました<br />";						
			}

		}
			
		$cut_position = 0;		//	カット開始の初期化
		$ga_file_num ++;

	}		// foreach（画像）の終了		----------------------------------------
	//				imagedestroy($dest);
	//			imagedestroy($src);

//	画像ファイル処理 終了	=====================================================================
//	話フォルダ内　終了　================================================================

//	echo "配列の中身は<br />";
//	print_r($file_list);

	$wa_folder_num ++;

		}

//	画像カット残り（前残り）ファイルを削除する	GD時代にココにあった。一応残し。
//		$delete_file =  "C:/xampp/htdocs/working/03_output_cut/mae_nokori.jpg";
//		if (unlink($delete_file)){
// 	 		echo $delete_file . " を削除しました。<br />";
//		}else{
//			echo $delete_file . " の削除に失敗しました<br />";						
//		}

	}

}
closedir($wa_dir);
//	話フォルダs　終了	================================================================


//	タイトルフォルダ　終了	================================================================

$title_folder_num ++;
		}
	}

}
closedir($h_dh);

//	ホームフォルダ　終了	====================================================================

	}


//	動作終了後、表示	=======================================================================

	echo "<br />＜====================　処理終了　====================＞<br />";

//	include "99_end.html";


?>

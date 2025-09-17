<?php
$log_content = "";

ini_set("max_execution_time",0);		//https://mrgoofy.hatenablog.com/entry/20100922/1285168658
ini_set('memory_limit', '512M');

/**
 * Ensure APP1 Exif blocks carry at least one real IFD0 tag.
 */
function has_real_exif(string $path): bool {
    $fh = @fopen($path, 'rb');
    if (!$fh) return false;

    if (fread($fh, 2) !== "\xFF\xD8") {
        fclose($fh);
        return false;
    }

    while (!feof($fh)) {
        $b = fread($fh, 1);
        if ($b === false) break;
        if ($b !== "\xFF") continue;
        do { $m = fread($fh, 1); } while ($m === "\xFF");
        if ($m === false) break;
        $marker = ord($m);
        if ($marker === 0xDA || $marker === 0xD9) break;

        $lenBin = fread($fh, 2);
        if (strlen($lenBin) < 2) break;
        $len = unpack('n', $lenBin)[1];
        if ($len < 2) break;
        $payload = $len > 2 ? fread($fh, $len - 2) : '';

        if ($marker === 0xE1 && strlen($payload) >= 10) {
            if (substr($payload, 0, 6) === "Exif\0\0") {
                $tiff = substr($payload, 6);
                if (strlen($tiff) < 8) {
                    fclose($fh);
                    return false;
                }
                $isLE   = (substr($tiff, 0, 2) === "II");
                $ifd0off = $isLE ? unpack('V', substr($tiff, 4, 4))[1]
                                 : unpack('N', substr($tiff, 4, 4))[1];

                if ($ifd0off + 2 <= strlen($tiff)) {
                    $cntBin = substr($tiff, $ifd0off, 2);
                    $count  = $isLE ? unpack('v', $cntBin)[1] : unpack('n', $cntBin)[1];
                    fclose($fh);
                    return ($count > 0);
                }
                fclose($fh);
                return false;
            }
        }
    }
    fclose($fh);
    return false;
}


if (!class_exists('MetaInspectorV3')) {
class MetaInspectorV3 {
    // APP1 + IFD0 を軽くチェックして「中身のあるExif」を見極める
    public static function hasRealExifIFD0(string $path): array {
        $reason = ['app1' => false, 'ifd0_tags' => 0, 'exif_read_count' => 0];
        $fh = @fopen($path, 'rb');
        if (!$fh) return $reason;
        if (fread($fh, 2) !== "\xFF\xD8") {
            fclose($fh);
            return $reason;
        }
        while (!feof($fh)) {
            $b = fread($fh, 1);
            if ($b === false) break;
            if ($b !== "\xFF") continue;
            do { $m = fread($fh, 1); } while ($m === "\xFF");
            if ($m === false) break;
            $mk = ord($m);
            if ($mk === 0xDA || $mk === 0xD9) break;

            $lenBin = fread($fh, 2);
            if (strlen($lenBin) < 2) break;
            $len = unpack('n', $lenBin)[1];
            if ($len < 2) break;
            $payload = $len > 2 ? fread($fh, $len - 2) : '';

            if ($mk === 0xE1 && substr($payload, 0, 6) === "Exif\0\0") {
                $reason['app1'] = true;
                $tiff = substr($payload, 6);
                if (strlen($tiff) >= 8) {
                    $le  = (substr($tiff, 0, 2) === "II");
                    $off = $le ? unpack('V', substr($tiff, 4, 4))[1]
                               : unpack('N', substr($tiff, 4, 4))[1];
                    if ($off + 2 <= strlen($tiff)) {
                        $cntBin = substr($tiff, $off, 2);
                        $cnt    = $le ? unpack('v', $cntBin)[1]
                                      : unpack('n', $cntBin)[1];
                        $reason['ifd0_tags'] = $cnt;
                    }
                }
                break;
            }
        }
        fclose($fh);
        if (function_exists('exif_read_data')) {
            $arr = @exif_read_data($path, 'ANY', true, false);
            if (is_array($arr)) {
                $reason['exif_read_count'] = count($arr);
            }
        }
        return $reason;
    }

    public static function checkProfiles(string $filepath): array {
        $profiles = [];
        $why = self::hasRealExifIFD0($filepath);
        $exifInfo = [
            'has_tags' => false,
            'has_real' => ($why['ifd0_tags'] ?? 0) > 0,
        ];

        try {
            $im = new \Imagick($filepath);
            $profileLabels = [
                'iptc' => 'IPTC',
                'xmp'  => 'XMP',
                'icc'  => 'ICC',
            ];
            foreach ($profileLabels as $key => $label) {
                try {
                    $data = $im->getImageProfile($key);
                    if ($data !== false && strlen($data) > 0) {
                        $profiles[] = $label . ':データあり';
                    }
                } catch (\ImagickException $e) {}
            }

            // --- EXIF 中身チェック ---
            if (function_exists('exif_read_data') && preg_match('/\.jpe?g$/i', $filepath)) {
                $exifArr = @exif_read_data($filepath, 'ANY', true, false);
                $hasUseful = false;
                if (is_array($exifArr)) {
                    foreach ($exifArr as $section => $data) {
                        if (in_array($section, ['FILE', 'COMPUTED'], true)) continue;
                        if (is_array($data) && count($data) > 0) {
                            $hasUseful = true;
                            break;
                        }
                    }
                }
                $exifInfo['has_tags'] = $hasUseful;
            }

            $im->destroy();
        } catch (\Exception $e) {}

        // --- 判定: 殻は無視、中身ありだけ検出 ---
        if ($exifInfo['has_tags'] || $exifInfo['has_real']) {
            $profiles[] = 'EXIF:データあり';
        }

        return $profiles;
    }

    public static function labelFor(string $filepath): string {
        $p = self::checkProfiles($filepath);
        return empty($p) ? '[メタ情報なし]' : '[メタ情報: ' . implode(' / ', $p) . ']';
    }
}
}




//	連番チェックを入れる
//	（DLか解凍かupかコピーの中で、ファイル抜けの可能性アリ）
//		配列の順番数と、ファイル名の照らし合わせ？

//	画像サイズのチェック

//	拡張子チェック

//	jpeg／jpg、gif、png、bmpのチェック

//	拡張子にかかわらず保存形式チェック


//	各階層のフォルダ数、
//	格納しているフォルダ、ファイルの数量を表示

//	チェック内容を、csvにファイル出力（excelで見られる）


//	02チェックとして、
//	出力ファイルのチェックシート出し


//	array_push($file_list, $file);								//画像ファイルの配列化
//	echo "$file_num:" . $file_list[$file_num]  . "<br />";

//	$image_size = getimagesize($temp_dir ."/" . $file);			//画像ファイルの数値（配列）を取得
//	var_export($image_size) ;
//	echo "<br />";
//	echo $image_size[1];										//画像ファイルの高さのみを抽出
//	echo "<br />";


//	$result = glob('C:/xampp/htdocs/working/02_material_gousei/*');
//	var_dump($result);


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
echo "<b>作業内容：　01・作業前、データ内容のチェック</b>";
echo "<br /><br />";				

//	ヘッダー表示終了	----------------------------------------------------------


//	処理フォルダ　=================================================================================

	$home_dir = "C:/xampp/htdocs/working/01_check_pre/";		
	$out_dir = "C:/xampp/htdocs/working/02_output_gousei/";		
	
    if ($h_dh = opendir($home_dir)) {

//	ログファイルの用意	----------------------------------------------------------------
		$filename = 'C:/xampp/htdocs/working/10_output_logs/checklog_01.txt';
 
		//ファイルがすでに存在する場合には処理を行わない
		if(!file_exists($filename)){
			touch($filename);
		}
//	------------------------------------------------------------------------------------

		$title_folder_list = array();
		$title_folder_num = 0;

        while (($title_folder = readdir($h_dh)) !== false) {						//タイトルフォルダsを取得

			if ($title_folder != "." && $title_folder != "..") {				

	            array_push($title_folder_list, $title_folder);
				echo "<br />タイトルフォルダ " . $title_folder_num + 1 . "：" . $title_folder_list[$title_folder_num] ;
				echo "　=============================<br />";				

//	ログ記入　----------------------------------------------
				$content = "\rタイトルフォルダ " . $title_folder_num + 1 . "：" . $title_folder_list[$title_folder_num] . "　=============================\r";
				$fp = fopen($filename,'w');
				fwrite($fp,$content);
//				fclose($fp);
//	----------------------------------------------

				$title_dir = $home_dir . $title_folder_list[$title_folder_num] . "/";	//タイトルのディレクトリ


//	タイトルフォルダ内	========================================================================

    if ($t_dh = opendir($title_dir)) {

		$wa_folder_list = array();
		$wa_folder_num = 0;

        while (($wa_folder = readdir($t_dh)) !== false) {							//タイトルフォルダ（180ガール）内

			if ($wa_folder != "." && $wa_folder != "..") {				

	            array_push($wa_folder_list, $wa_folder);
				echo "<br />話フォルダ " . $wa_folder_num + 1 . "： " . $wa_folder_list[$wa_folder_num] . "　---------------------------------------------------------------------<br />";

//	ログ記入　----------------------------------------------
				$content = "話フォルダ " . $wa_folder_num + 1 . "： " . $wa_folder_list[$wa_folder_num] . "　---------------------------------------------------------------------\r";
//				$fp = fopen($filename,'w');
				fwrite($fp,$content);
//				fclose($fp);
//	----------------------------------------------


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
//						echo  . "　解像度：" . $kaizodo_info[0]
//						echo "<br />";

						if ($image_size[2] == "1") {		//	使えるが、$image_size['mime']がイキ。
							$type = "gif";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefromgif($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

// メタ情報判定
$metaLabel = MetaInspectorV3::labelFor($file_list[$file_num]);
echo " " . $metaLabel;
$log_content .= "num " . $file_num . "：" . $file . "　" . $metaLabel . "\r\n";



							echo "<br />";

						} elseif ($image_size[2] == "2") {
							$type = "jpeg";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefromjpeg($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];

    // メタ情報判定
    $metaLabel = MetaInspectorV3::labelFor($file_list[$file_num]);
    echo " " . $metaLabel;
    $log_content .= "num " . $file_num . "：" . $file . "　" . $metaLabel . "\r\n";



    echo "<br />";

						} elseif ($image_size[2] == "3") {
							$type = "png";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefrompng($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];
							echo "<br />";

						} elseif ($image_size[2] == "5") {
							$type = "psd";							

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							echo "　解像度：" . $imageResolution['x'];
							echo "<br />";

						} elseif ($image_size[2] == "6") {
							$type = "bmp";

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							$kaizodo = imagecreatefrombmp($file_list[$file_num]);	//解像度取得用のファイル取得
							$kaizodo_info = imageresolution($kaizodo);				//解像度取得。必須だが、タイプ検出とバッティング中
							echo "　解像度：" . $kaizodo_info[0];
							echo "　解像度：" . $imageResolution['x'];
							echo "<br />";

						} elseif ($image_size[2] == "7") {
							$type = "tiff";							

							echo "num " . $file_num . "：" . $file . "　幅：" . $image_size[0] . "　高さ：" . $image_size[1] . 
							"　種別：" . $image_size['mime'];									//画像ファイルの高さのみを抽出
							echo "　解像度：" . $imageResolution['x'];
							echo "<br />";
						}

//					echo $type . "<br />";

//	https://www.weblio.jp/content/image_type_to_mime_type


//	参考：https://www.php.net/manual/ja/function.image-type-to-mime-type.php
//		https://www.php.net/manual/ja/function.imageresolution.php

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
	$im->resizeImage((int)$kotei_w, (int)$henkan_h, Imagick::FILTER_LANCZOS, 1);	//	画像サイズ変更、デスクトップpc、php8.2.27で
																					//	エラーが出たためGPT回答反映（2025/1/20）

	$result->addImage($im);
	$kasan_h += $henkan_h;

	//					$im = new Imagick($file_list[$file_num]);		//	解像度取得のテスト
	//					print_r(imageresolution($result));
	//					echo imageresolution($result);


	}	//foreach画 終り	--------------------------------------------------------------
				//	https://www.php.net/manual/ja/imagick.appendimages.php
					// 配列終り：https://kakechimaru.com/php_foreach_first_last/
	
	}
	closedir($wa_dir);			//	話フォルダ内　終了	=========================================
						
// ファイル名から番号を抽出し、連番チェックを行う （copilot作成）-----------------------------
$file_numbers = []; // ファイル番号を格納する配列
$current_folder = basename($was_dir); // 現在のフォルダ名を取得

// ログ用の初期化
$log_content = ""; // 各フォルダごとにリセット

// 対象フォルダ内のファイルを一覧取得
foreach ($file_list as $file_path) {
    $file_name = basename($file_path); // ファイル名を取得
    if (preg_match('/(\d+)\./', $file_name, $matches)) { // ファイル名から番号を抽出
        $file_numbers[] = (int)$matches[1]; // 抽出した番号を数値として配列に追加
    }
}

// ファイルリストをログに記録
//	$log_content .= "ファイルリスト:\r\n" . implode("\r\n", $file_list) . "\r\n";

// 抽出した番号リストをログに記録
//	$log_content .= "抽出した番号リスト:\r\n" . implode(', ', $file_numbers) . "\r\n";

// 連番チェック
if (!empty($file_numbers)) {
    sort($file_numbers); // 番号を昇順にソート
    $min_number = min($file_numbers); // 最小値を取得
    $max_number = max($file_numbers); // 最大値を取得

    // ソート後の番号リストをログに記録
//    $log_content .= "ソート後の番号リスト:\r\n" . implode(', ', $file_numbers) . "\r\n";

    // 最小値と最大値をログに記録
//    $log_content .= "最小値: $min_number, 最大値: $max_number\r\n";

    // 理論的な完全連番リストを作成
    $expected_numbers = range($min_number, $max_number);

    // 理論的な完全連番リストをログに記録
//    $log_content .= "理論的な完全連番リスト:\r\n" . implode(', ', $expected_numbers) . "\r\n";

    // 存在しない番号を差分で求める
    $missing_numbers = array_diff($expected_numbers, $file_numbers);

    // 存在しない番号をログに記録
    if (!empty($missing_numbers)) {
//        $log_content .= "欠けている番号:\r\n" . implode(', ', $missing_numbers) . "\r\n";
		$log_content .= "欠けている番号:\r\n" . implode(', ', array_map(function($num) {
		    return sprintf('%03d', $num); // 3桁表記に変換
			}, $missing_numbers)) . "\r\n";
    } else {
        $log_content .= "欠けている番号はありません。\r\n";
    }
} else {
    $log_content .= "ファイルが見つかりませんでした。\r\n";
}

// ログファイルに記録
file_put_contents($filename, $log_content, FILE_APPEND);

// デバッグ用：画面にも出力
echo nl2br($log_content);

// 各フォルダごとにログ内容をリセット
$log_content = ""; // 次のフォルダに持ち越さないようにリセット

// 連番チェック終了 -------------------------------------------------------------


// デバッグ用：画面にも出力
echo nl2br($log_content);
						
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



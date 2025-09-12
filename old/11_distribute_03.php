<?php

//	glob参考：https://magazine.techacademy.jp/magazine/39376
//	glob 名前のみ：https://magazine.techacademy.jp/magazine/40769

//	未装備
//	フォルダもコピー
//	https://qiita.com/r-fuji/items/4736d9598a215b289dec


//	=================================================================================

	$hand_dir = "C:/xampp/htdocs/working/11_distribute/01_shot/";		
	$post_dir = "C:/xampp/htdocs/working/11_distribute/02_posts/";		
	
//	$target = "C:/xampp/htdocs/working/11_distribute/01_shot/dummy.txt";


//	$in_hand = glob("C:/xampp/htdocs/working/11_distribute/01_shot/*.*", GLOB_NOSORT);
//		print_r($in_hand);
//		echo "</br></br>";
		
		if ($h_dh = opendir($hand_dir)) {

			$shot_files_list = array();
			$shot_num = 0;
	
			while (($shot_files = readdir($h_dh)) !== false) {				//
	
				if ($shot_files != "." && $shot_files != "..") {				
	
					array_push($shot_files_list, $shot_files);
//					echo "コピー用ファイル名1：" . $shot_files . "</br>";
//					echo "コピー用ファイル名2：" . $shot_files_list[$shot_num] . "</br>";
				}
				$shot_num ++;
			}

		$count = 0;
		foreach ($shot_files_list as $path) {

			echo "(配列）コピー用ファイル名：" . $shot_files_list[$count] . "</br>";
			$count ++;

			}
		}

		echo "</br>=======================</br></br>";

//	=================================================================================

    if ($p_dh = opendir($post_dir)) {

		$goal_folders_list = array();
		$num = 0;

        while (($goal_folders = readdir($p_dh)) !== false) {				//

			if ($goal_folders != "." && $goal_folders != "..") {				

				array_push($goal_folders_list, $goal_folders);
				echo "配布フォルダ名：" . $goal_folders_list[$num] . "　-------------------------------------</br>";



			$check_num = 0;
			foreach ($shot_files_list as $path) {

			$target = $hand_dir . $shot_files_list[$check_num];

			$mark = $post_dir . $goal_folders_list[$num] . "/" . $shot_files_list[$check_num];
				echo $mark . "</br>";

				$act = copy($target, $mark);
				if ($act) {
		   			echo "コピー終了" . "</br></br>";
				} else {
					echo "コピー不全、エラー注意！" . "</br></br>";
				}
				$check_num ++;
			
			}	
				$num ++;

		}

	}

	}
//	=================================================================================


?>
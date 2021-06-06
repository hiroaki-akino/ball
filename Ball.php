<?php

/*
=============================================
作成者：秋野浩朗
作成日：2020/7/13
修正日：----/-/-
概要　：ボールの軌道予想
=============================================

■ 参考URL
JS submit()の留意点　https://techblog.recochoku.jp/5887
JS キャスト(DOM操作時は全て文字列で取得される？)　https://blog.goo.ne.jp/kori39/e/03d9ce6a3180e9a553644a763fc42542
*/ 


// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　変数（ローカルファイル内の共通変数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

$source_name							=	basename(__FILE__);
$delimiter								= "<br><hr><br>";
$err_msg									=	null;
$field										=	"";		// フィールド（テーブルタグ格納）
$hidden_tags 							=	"";		// hiddenタグ群
$move_ball_js							= "";		// ボール動かす時に使う追加のJS読み込み用タグ
$ball_move_contents				= "";		// ボール動かす時のコンテンツタグ群
$field_y_point						=	"20";		// フィールドの最大縦マス数（数値）
$field_x_point						=	"20";		// フィールドの最大横マス数（数値）
$ball_default_y_point			=	"1";		// ボールの初期位置の縦マス数（数値）
$ball_default_x_point			=	"1";		// ボールの初期位置の横マス数（数値）
$ball_default_y_on_border	= false;// ボールの初期位置が枠沿いかどうか（true:枠沿い / false:それ以外）
$ball_default_x_on_border	= false;// ボールの初期位置が枠沿いかどうか（true:枠沿い / false:それ以外)
$ball_default_y_on_corner	= false;// ボールの初期位置が四隅かどうか（true:四隅 / false:それ以外）
$ball_default_x_on_corner	= false;// ボールの初期位置が四隅かどうか（true:四隅 / false:それ以外)
$ball_current_y_point			=	"";		// ボールの現在位置の縦マス数（数値）
$ball_current_x_point			=	"";		// ボールの現在位置の横マス数（数値）
$ball_current_direction_y	= "";		// ボールの現在の縦方向の向き（true:down  / false:up ）
$ball_current_direction_x	=	"";		// ボールの現在の横方向の向き（true:right / false:left ）
$ball_move_y_point				=	"1";		// １回の移動でボールが縦に進むマス数（数値）
$ball_move_x_point				=	"1";		// １回の移動でボールが横に進むマス数（数値）
$ball_move_count					=	"10";		// ボールを動かす回数
$ball_move_speed					= "0.1";
$ball_direction_patern		=	"7";		// 移動方向パターン（数値）
$ball_direction_list			= array(
	 0=>"左上", 1=>"上",	2=>"右上",
	 3=>"左",		4=>"右",
	 5=>"左下",	6=>"下", 7=>"右下");
$ball_expect_y_list				= array();	// ボール軌道シミュレーション結果リスト（Y軸）
$ball_expect_x_list	 			= array();	// ボール軌道シミュレーション結果リスト（X軸）
$ball_last_y_point				= "";				// ボール軌道シミュレーション（計算による最終到達地点の算出）
$ball_last_x_point				= "";				// ボール軌道シミュレーション（計算による最終到達地点の算出）

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　関数（ローカルファイル内の共通関数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

/*【関数】サニタイズ（XSS対策） */
function h($val){
	return htmlspecialchars($val);
}

/*【関数】検証用（ preタグ + var_dump() ）*/
function pre_dump($arg){
	echo "<pre>";
	var_dump($arg);
	echo "</pre>";
}

/*【関数】フィールドの生成（	i = Y軸（縦マス）、j = X軸（横マス）） */
function make_field($y, $x){
	global $field;
	$field = "<table class=\"table\">";
	$field .= "<tr>";
	$field .= "<th></th>";
	for($j = 1 ; $j <= $x ; $j++){
		$field .= "<th>$j</th>";
	}
	$field .= "</tr>";
	for($i = 1 ; $i <= $y ; $i++){
		$field .= "<tr id=\"$i\" name=\"$i\">";
		for($j = 1 ; $j <= $x ; $j++){
			if($j == 1){ 
				$field .= "<th>$i</th>"; 
			}
			$field .= "<td id=\"$i-$j\" name=\"$i-$j\"></td>";
		}
		$field .= "</tr>";
	}
	$field .= "</table>";
}

/*【関数】最初のボールの向きを決める */
function default_direction($ball_direction_patern){
	global $ball_current_direction_y, $ball_current_direction_x;
	switch($ball_direction_patern){
		// 左上方向への移動の場合
		case 0 :
			$ball_current_direction_y = false;
			$ball_current_direction_x = false;
			break;
		// 真上方向への移動の場合
		case 1 :
			$ball_current_direction_y = false;
			$ball_current_direction_x = null;
			break;
		// 右上方向への移動の場合
		case 2 :
			$ball_current_direction_y = false;
			$ball_current_direction_x = true;
			break;
		// 左方向への移動の場合
		case 3 :
			$ball_current_direction_y = null;
			$ball_current_direction_x = false;
			break;
		// 右方向への移動の場合
		case 4 :
			$ball_current_direction_y = null;
			$ball_current_direction_x = true;
			break;
		// 左下方向への移動の場合
		case 5 :
			$ball_current_direction_y = true;
			$ball_current_direction_x = false;
			break;
		// 真下方向への移動の場合
		case 6 :
			$ball_current_direction_y = true;
			$ball_current_direction_x = null;
			break;
		// 右下方向への移動の場合
		case 7 :
			$ball_current_direction_y = true;
			$ball_current_direction_x = true;
			break;
	}
}

/*【関数】ボールの初期値が枠線上がどうかを判定 */
// 引数：フィールドの閾値、ボールの初期位置、ボールの方向
// 備考：ボールの初期位置が枠沿いの時はいきなり反射するので前処理が必要
function default_on_border($f_y, $f_x, $d_y, $d_x, $d_p){
	global $ball_default_y_on_border, $ball_default_x_on_border;
	// ボールのY軸（縦）の初期位置が枠沿いの時
	if( $d_y == $f_y || $d_y == 1 ){
		$ball_default_y_on_border = true;
	}
	// ボールのX軸（横）の初期位置が枠沿いの時
	if( $d_x == $f_x || $d_x == 1 ){
		$ball_default_x_on_border = true;
	}
}

/*【関数】ボールの初期値が四隅がどうかを判定 */
// 引数：フィールドの閾値、ボールの初期位置
// 備考：ボールの初期位置が四隅の時はいきなり真逆に反射するので前処理が必要
function default_on_corner($f_y, $f_x, $d_y, $d_x, $d_p){
	global $ball_default_y_on_corner, $ball_default_x_on_corner;
	// ボールの初期位置が四隅の何れかで、最初の向きが四隅に対して真逆になる時
	if( ($d_y == 1		&& $d_x == 1		&& $d_p == 0)	||	// 初期位置：左上、初期方向：左上
			($d_y == 1		&& $d_x == $f_x && $d_p == 2)	||	// 初期位置：右上、初期方向：右上
			($d_y == $f_y	&& $d_x == 1		&& $d_p == 5)	||	// 初期位置：左下、初期方向：左下
			($d_y == $f_y && $d_x == $f_x	&& $d_p == 7) ){	// 初期位置：右上、初期方向：右上
			$ball_default_y_on_corner = true;
			$ball_default_x_on_corner = true;
	}
}

/*【関数】次のボールのY（縦）位置を決める */
// 引数：フィールドの最大Y値、ボールの初期値、現在のボールの位置、ボールが１回に動くマス数、初期値が枠線上かどうかのフラグ、ボールを動かす回数
// 返値：次にボールが動くY（縦）位置
function next_y_point($f_y, $d_y, $c_y, $m_y, $on_border, $on_corner, $count){
	global $ball_current_direction_y;

	// Y軸が動かない時（左方向のみor右方向のみの移動）
	if(is_null($ball_current_direction_y)){
		$result = $c_y;
		return $result;
	}
	// Y軸が動く時（上記以外の移動）ボールが１回で動く位置を算出
	for($i = 1 ; $i <= $m_y ; $i++){
		// 初期値が枠線上で、初動の場合
		if($on_border && $count == 1){
			$flug = true;
			// 初期値が枠の上限上で、ボールの向きが上向きで、ボールの移動が１マス目（$i=1）の時（いきなり反射する）
			if($d_y == 1 && !$ball_current_direction_y && $i == 1){
				// 現在のボールの向きを下向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
				$ball_current_direction_y = true;
				// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
				if($on_corner){
					$c_y++;
				}
				$flug = false;
			}
			// 初期値が枠の下限上で、ボールの向きが下向きで、ボールの移動が１マス目（$i=1）の時時（いきなり反射する）
			if($d_y == $f_y && $ball_current_direction_y && $i == 1){
				// 現在のボールの向きを上向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
				$ball_current_direction_y = false;
				// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
				if($on_corner){
					$c_y--;
				}
				$flug = false;
			}
			// 上記以外の時（ボールの移動が２マス目以降の時）
			if($flug){
				if(!$ball_current_direction_y){
					// ボールの現在の向きが上向きの時
					// 現在のボールの位置を移動（１マス減らす）
					$c_y--;
				}else{
					// ボールの現在の向きが下向きの時
					// 現在のボールの位置を移動（１マス増やす）
					$c_y++;
				}
			}
		}else{
		// 初期値が枠線上以外かつ、初動ではない時
			switch($c_y){
				// 現在のボールの位置が枠の上限上の時
				case $c_y == 1 :
					// 現在のボールの向きを下向きに変える
					$ball_current_direction_y = true;
					// 現在のボールの位置を移動（１マス増やす）
					$c_y++;
					break;
				// 現在のボールの位置が枠の下限上の時
				case $c_y == $f_y :
					// 現在のボールの向きを上向きに変える
					$ball_current_direction_y = false;
					// 現在のボールの位置を移動（１マス減らす）
					$c_y--;
					break;
				// 現在のボールが枠線以外の時
				default :
					if(!$ball_current_direction_y){
						// ボールの現在の向きが上向きの時
						// 現在のボールの位置を移動（１マス減らす）
						$c_y--;
					}else{
						// ボールの現在の向きが下向きの時
						// 現在のボールの位置を移動（１マス増やす）
						$c_y++;
					}
			}
		}
		$result = $c_y;
		// echo "$i:",var_dump($ball_current_direction_y),":$result<br>";
	}
	return $result;
}

/*【関数】次のボールのX（横）位置を決める */
// 引数：フィールドの最大X値、ボールの初期値、現在のボールの位置、ボールが１回に動くマス数、初期値が枠線上かどうかのフラグ、ボールを動かす回数
// 返値：次にボールが動くX（横）位置
function next_x_point($f_x, $d_x, $c_x, $m_x, $on_border, $on_corner, $count){
	global $ball_current_direction_x;

	// Y軸が動かない時（左方向のみor右方向のみの移動）
	if(is_null($ball_current_direction_x)){
		$result = $c_x;
		return $result;
	}
	// X軸が動く時（上記以外の移動）ボールが１回で動く位置を算出
	for($i = 1 ; $i <= $m_x ; $i++){
		// 初期値が枠線上で、初動の場合
		if($on_border && $count == 1){
			$flug = true;
			// 初期値が枠の上限上で、ボールの向きが左向きで、ボールの移動が１マス目（$i=1）の時（いきなり反射する）
			if($d_x == 1 && !$ball_current_direction_x && $i == 1){
				// 現在のボールの向きを右向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
				$ball_current_direction_x = true;
				// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
				if($on_corner){
					$c_x++;
				}
				$flug = false;
			}
			// 初期値が枠の下限上で、ボールの向きが右向きで、ボールの移動が１マス目（$i=1）の時時（いきなり反射する）
			if($d_x == $f_x && $ball_current_direction_x && $i == 1){
				// 現在のボールの向きを左向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
				$ball_current_direction_x = false;
				// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
				if($on_corner){
					$c_x--;
				}
				$flug = false;
			}
			// 上記以外の時（ボールの移動が２マス目以降の時）
			if( $flug ){
				if(!$ball_current_direction_x){
					// ボールの現在の向きが左向きの時
					// 現在のボールの位置を移動（１マス減らす）
					$c_x--;
				}else{
					// ボールの現在の向きが右向きの時
					// 現在のボールの位置を移動（１マス増やす）
					$c_x++;
				}
			}
		}else{
		// 初期値が枠線上以外かつ、初動ではない時
			switch($c_x){
				// 現在のボールの位置が枠の上限上の時
				case $c_x == 1 :
					// 現在のボールの向きを右向きに変える
					$ball_current_direction_x = true;
					// 現在のボールの位置を移動（１マス増やす）
					$c_x++;
					break;
				// 現在のボールの位置が枠の下限上の時
				case $c_x == $f_x :
					// 現在のボールの向きを左向きに変える
					$ball_current_direction_x = false;
					// 現在のボールの位置を移動（１マス減らす）
					$c_x--;
					break;
				// 現在のボールが枠線以外の時
				default :
					if(!$ball_current_direction_x){
						// ボールの現在の向きが左向きの時
						// 現在のボールの位置を移動（１マス減らす）
						$c_x--;
					}else{
						// ボールの現在の向きが右向きの時
						// 現在のボールの位置を移動（１マス増やす）
						$c_x++;
					}
			}
		}
		$result = $c_x;
		// echo "$i:",var_dump($ball_current_direction_x),":$result<br>";
	}
	return $result;
}

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　以降、各種処理　＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

if($_SERVER["REQUEST_METHOD"] == "POST"){

	$field_y_point					=	$_POST["field_y_point"];
	$field_x_point					= $_POST["field_x_point"];
	$ball_default_y_point		= $_POST["ball_default_y_point"];
	$ball_default_x_point		= $_POST["ball_default_x_point"];
	$ball_current_y_point		= $_POST["ball_default_y_point"];
	$ball_current_x_point		= $_POST["ball_default_x_point"];
	$ball_move_y_point			= $_POST["ball_move_y_point"];
	$ball_move_x_point			= $_POST["ball_move_x_point"];
	$ball_move_count				= $_POST["ball_move_count"];
	$ball_direction_patern	= $_POST["ball_direction_patern"];
	$ball_move_speed				= $_POST["ball_move_speed"];

	// ボールを動かすかどうか
	if( isset($_POST["move_ball"]) ){
		// 動かす場合は空のフィールドを作成
		make_field($field_y_point, $field_x_point);
		// ボールを動かすJSを追加で読み込む
		$move_ball_js = "<script type=\"text/javascript\" src=\"Ball_move.js\"></script>";
		// スピードを入れ忘れてたら0.3秒に設定する
		if(empty($ball_move_speed)){
			$ball_move_speed = 0.3;
		}

	}else{

		// ボールを動かさない場合（単なるシミュレーション）

		/* ---------- 以降、ボールの軌道をシミレーションして算出するパターン ---------- */ 
		// 軌道結果配列に初期値を格納
		$ball_expect_y_list[] = $ball_default_y_point;
		$ball_expect_x_list[] = $ball_default_x_point;

		// 入力された値からボールの最初の向きを決定
		default_direction($ball_direction_patern);

		// ボールの初期位置が枠沿いかどうかの判定
		default_on_border($field_y_point, $field_x_point, $ball_default_y_point, $ball_default_x_point, $ball_direction_patern);

		// ボールの初期位置が四隅かどうかの判定
		default_on_corner($field_y_point, $field_x_point, $ball_default_y_point, $ball_default_x_point, $ball_direction_patern);

		// ボールが動く回数分、ボールの軌道（X/Y軸の位置）を算出
		for($i = 1 ; $i <= $ball_move_count ; $i++){
			// 現在のボール位置を算出した値に移動させる
			$ball_current_y_point = next_y_point($field_y_point, $ball_default_y_point, $ball_current_y_point, $ball_move_y_point, $ball_default_y_on_border, $ball_default_y_on_corner, $i);
			$ball_current_x_point = next_x_point($field_x_point, $ball_default_x_point, $ball_current_x_point, $ball_move_x_point, $ball_default_x_on_border, $ball_default_x_on_corner, $i);
			// 現在のボール位置（算出した結果）を軌道結果配列に格納
			$ball_expect_y_list[] = $ball_current_y_point;
			$ball_expect_x_list[] = $ball_current_x_point;
		}
		//pre_dump($ball_expect_y_list);
		//pre_dump($ball_expect_x_list);


		/* ----------- 以降、ボールの軌道を計算で算出するパターン（ループ処理なしでY軸値を算出） ---------- */

		// 入力された値からボールの最初の向きを決定
		default_direction($ball_direction_patern);

		// ボールの初期位置が枠沿いかどうかの判定
		default_on_border($field_y_point, $field_x_point, $ball_default_y_point, $ball_default_x_point, $ball_direction_patern);

		// ボールの総移動マスを計算（反射やフィールド上限を無視した暫定マス数）
		$ball_move_sum_y_point = $ball_move_y_point * $ball_move_count;
		// echo "総移動マス（反射・フィールド上限無視）：", $ball_move_sum_y_point , "<br>";

		if($ball_default_y_on_border){
			// 初期位置がフィールド上限上にあり、ボールの向きが上の時（いきなり反射する時）
			if($ball_default_y_point == 1 && !$ball_current_direction_y){
				$ball_current_direction_y = true;
				if(!$ball_default_y_on_corner){
					$ball_move_sum_y_point--;
				}
				// $reflect_y_count++;
			}
			// 初期位置がフィールド下限上にあり、ボールの向きが下の時（いきなり反射する時）
			if($ball_default_y_point == $field_y_point && $ball_current_direction_y){
				$ball_current_direction_y = false;
				if(!$ball_default_y_on_corner){
					$ball_move_sum_y_point--;
				}
				// $reflect_y_count++;
			}
		}

		// 何回反射する事になるかを計
		$reflect_y_count = 0;
		if(!$ball_current_direction_y){
			if($ball_move_sum_y_point > $ball_default_y_point){
				$reflect_y_count = floor( ($ball_move_sum_y_point - $ball_default_y_point) / ($field_y_point - 1) ) + 1 ;
			}
		}else{
			if($ball_move_sum_y_point > ($field_y_point - $ball_default_y_point) ){
				$reflect_y_count = floor( ($ball_move_sum_y_point - ($field_y_point - $ball_default_y_point + 1) ) / ($field_y_point - 1) ) + 1 ;
			}
		}

		// echo "反射回数：" , $reflect_y_count , "<br>" ;

		// 反射があるかどうかを判定
		if($reflect_y_count >= 1){

			// 初期値から反射に至るまでのマス数を減らす。
			// echo "a:" , $ball_move_sum_y_point  , "<br>";
			!$ball_current_direction_y ? $ball_move_sum_y_point -= ($ball_default_y_point - 1) : $ball_move_sum_y_point -= ($field_y_point - $ball_default_y_point) ;
			// echo "b:" , $ball_move_sum_y_point , "<br>";

			// 総移動マス数から、連続反射中（反射した数-1回分）のフィールド移動分をマイナスする。
			// 連続反射後(=反射回数目)は、フィールドの途中でボールが止まっているので別途計算する。
			// echo "c:" , $ball_move_sum_y_point , "<br>";
			// フィールド移動分（=フィールド枠線上を除外した移動マス）をマイナスする。
			$ball_move_sum_y_point -= ($field_y_point - 2) * ($reflect_y_count - 1);
			// echo "d:" , $ball_move_sum_y_point , "<br>";
			// 連続反射中に枠線上にボールが位置した回数をマイナスする。
			$ball_move_sum_y_point -= ($reflect_y_count - 1) ;
			//echo "e:" , $ball_move_sum_y_point , "<br>";
		}

		if($reflect_y_count % 2 != 0){
			// 反射回数が奇数の時は、最後のボールの向きを逆にする（偶数の時は同じ方向）
			$ball_current_direction_y = !$ball_current_direction_y;
		}

		// ボールの最終位置（反射があった場合は反射後の位置）を算出する
		if(!$ball_current_direction_y){
			// 向きが上向きの時は枠下限から最終位置を算出する
			$ball_move_sum_y_point = $field_y_point - $ball_move_sum_y_point ;
		}else{
			// 向きが下向きの時は枠上限から最終位置を算出する（+1は枠上限値に移動したとみなす必要があるため）
			$ball_move_sum_y_point = $ball_move_sum_y_point + 1 ;
		}

		// echo "LAST:" , $ball_move_sum_y_point  , "<br>";
		$ball_last_y_point = $ball_move_sum_y_point;



		/* ----------- 以降、ボールの軌道を計算で算出するパターン（ループ処理なしでX軸値を算出） ---------- */

		// 入力された値からボールの最初の向きを決定
		default_direction($ball_direction_patern);

		// ボールの初期位置が枠沿いかどうかの判定
		default_on_border($field_y_point, $field_x_point, $ball_default_y_point, $ball_default_x_point, $ball_direction_patern);

		// ボールの総移動マスを計算（反射やフィールド上限を無視した暫定マス数）
		$ball_move_sum_x_point = $ball_move_x_point * $ball_move_count;
		// echo "総移動マス（反射・フィールド上限無視）：", $ball_move_sum_x_point , "<br>";

		if($ball_default_x_on_border){
			// 初期位置がフィールド上限上にあり、ボールの向きが上の時（いきなり反射する時）
			if($ball_default_x_point == 1 && !$ball_current_direction_x){
				$ball_current_direction_x = true;
				if(!$ball_default_x_on_corner){
					$ball_move_sum_x_point--;
				}
				// $reflect_x_count++;
			}
			// 初期位置がフィールド下限上にあり、ボールの向きが下の時（いきなり反射する時）
			if($ball_default_x_point == $field_x_point && $ball_current_direction_x){
				$ball_current_direction_x = false;
				if(!$ball_default_x_on_corner){
					$ball_move_sum_x_point--;
				}
				// $reflect_x_count++;
			}
		}

		// 何回反射する事になるかを計
		$reflect_x_count = 0;
		if(!$ball_current_direction_x){
			if($ball_move_sum_x_point > $ball_default_x_point){
				$reflect_x_count = floor( ($ball_move_sum_x_point - $ball_default_x_point) / ($field_x_point - 1) ) + 1 ;
			}
		}else{
			if($ball_move_sum_x_point > ($field_x_point - $ball_default_x_point) ){
				$reflect_x_count = floor( ($ball_move_sum_x_point - ($field_x_point - $ball_default_x_point + 1) ) / ($field_x_point - 1) ) + 1 ;
			}
		}

		// echo "反射回数：" , $reflect_x_count , "<br>" ;

		// 反射があるかどうかを判定
		if($reflect_x_count >= 1){

			// 初期値から反射に至るまでのマス数を減らす。
			// echo "a:" , $ball_move_sum_x_point  , "<br>";
			!$ball_current_direction_x ? $ball_move_sum_x_point -= ($ball_default_x_point - 1) : $ball_move_sum_x_point -= ($field_x_point - $ball_default_x_point) ;
			// echo "b:" , $ball_move_sum_x_point , "<br>";

			// 総移動マス数から、連続反射中（反射した数-1回分）のフィールド移動分をマイナスする。
			// 連続反射後(=反射回数目)は、フィールドの途中でボールが止まっているので別途計算する。
			// echo "c:" , $ball_move_sum_x_point , "<br>";
			// フィールド移動分（=フィールド枠線上を除外した移動マス）をマイナスする。
			$ball_move_sum_x_point -= ($field_x_point - 2) * ($reflect_x_count - 1);
			// echo "d:" , $ball_move_sum_x_point , "<br>";
			// 連続反射中に枠線上にボールが位置した回数をマイナスする。
			$ball_move_sum_x_point -= ($reflect_x_count - 1) ;
			//echo "e:" , $ball_move_sum_x_point , "<br>";
		}

		if($reflect_x_count % 2 != 0){
			// 反射回数が奇数の時は、最後のボールの向きを逆にする（偶数の時は同じ方向）
			$ball_current_direction_x = !$ball_current_direction_x;
		}

		// ボールの最終位置（反射があった場合は反射後の位置）を算出する
		if(!$ball_current_direction_x){
			// 向きが上向きの時は枠下限から最終位置を算出する
			$ball_move_sum_x_point = $field_x_point - $ball_move_sum_x_point ;
		}else{
			// 向きが下向きの時は枠上限から最終位置を算出する（+1は枠上限値に移動したとみなす必要があるため）
			$ball_move_sum_x_point = $ball_move_sum_x_point + 1 ;
		}

		// echo "LAST:" , $ball_move_sum_x_point  , "<br>";
		$ball_last_x_point = $ball_move_sum_x_point;



		// フィールドの生成（	i = Y軸（縦マス）、j = X軸（横マス））
		$field = "<h3>ボールの軌道予想図</h3>";
		$field .= "<table class=\"table\">";
		$field .= "<tr>";
		$field .= "<th></th>";
		for($j = 1 ; $j <= $field_x_point ; $j++){
			$field .= "<th>$j</th>";
		}
		$field .= "</tr>";
		for($i = 1 ; $i <= $field_y_point ; $i++){
			$field .= "<tr id=\"$i\" name=\"$i\">";
			for($j = 1 ; $j <= $field_x_point ; $j++){
				if($j == 1){ 
					$field .= "<th>$i</th>"; 
				}
				$field .= "<td id=\"$i-$j\" name=\"$i-$j\">";
				// ボールの軌道予想座標と一致すればボールを表示する
				for($k = 0 ; $k < count($ball_expect_x_list) ; $k++){
					if($i == $ball_expect_y_list[$k] && $j == $ball_expect_x_list[$k]){
						// デフォルト位置の場合はちょっと変える。
						if($k == 0){
							$field .= "🔴";
						}else{
							$field .= "●";
						}
					}
				}
				$field .= "</td>";
			}
			$field .= "</tr>";
		}
		$field .= "</table>";
	}

	/* hiddenタグの設定*/
	// フィールドのY軸/X軸の設定
	$hidden_tags .= "<input type=\"hidden\" name=\"field_y_point\" value=$field_y_point>";
	$hidden_tags .= "<input type=\"hidden\" name=\"field_x_point\" value=$field_x_point>";
	// ボールの初期値
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_default_y_point\" value=$ball_default_y_point>";
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_default_x_point\" value=$ball_default_x_point>";
	// ボールの現在値
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_current_y_point\" value=$ball_current_y_point>";
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_current_x_point\" value=$ball_current_x_point>";
	// ボールが動くマス数
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_move_y_point\" value=$ball_move_y_point>";
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_move_x_point\" value=$ball_move_x_point>";
	// ボールが動く回数
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_move_count\" value=$ball_move_count>";
	// ボールが進む方向のパターン
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_direction_patern\" value=$ball_direction_patern>";
	// ボールの現在の縦方向の向き（true:down  / false:up ）
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_current_direction_y\" value=$ball_current_direction_y>";
	// ボールの現在の横方向の向き（true:right / false:left ）
	$hidden_tags .= "<input type=\"hidden\" name=\"ball_current_direction_x\" value=$ball_current_direction_x>";

	$ball_move_contents .= "<input type=\"hidden\" name=\"move_ball\" value=\"true\">";
	$ball_move_contents .= "<br>";
	$ball_move_contents .= "ボールの移動スピード：<input type=\"number\" id=\"ball_move_speed\" name=\"ball_move_speed\" step=\"0.1\" min=\"0.1\" value=\"$ball_move_speed\" placeholder=\"0.3\">秒";
	$ball_move_contents .= "<br><br><br>";
	$ball_move_contents .= "<input type=\"submit\" class=\"move_start\" value=\"動かしてみる！\">";
	$ball_move_contents .= "<br><br>";
}

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　以降、画面表示　＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta charset="utf-8">
	<title>ボールの軌道シミュレーション</title>
	<script>

		// フォームの内容チェック（バリデーション）
		function err_check(form_id){

			var err_msg				= "";
			var valid_check		= true;
			var submit_check	= true;
			var form_tag			=	document.getElementById(form_id);
			var err_tag				= document.getElementById(form_id + "_err");

			var field_y_point					= document.getElementById("field_y_point").value;
			var field_x_point					= document.getElementById("field_x_point").value;
			var ball_default_y_point	= document.getElementById("ball_default_y_point").value;
			var ball_default_x_point	= document.getElementById("ball_default_x_point").value;
			var ball_direction_patern	= document.getElementById("ball_direction_patern").value;
			var ball_move_y_point			= document.getElementById("ball_move_y_point").value;
			var ball_move_x_point			= document.getElementById("ball_move_x_point").value;
			var ball_move_count				= document.getElementById("ball_move_count").value;

			// 最初に全項目の空文字チェック(見栄えの為に三項演算子を使用)
			!err_empty(field_y_point,					"field_y_point")					? valid_check = false : "" ;
			!err_empty(field_x_point, 				"field_x_point")					? valid_check	= false : "" ;
			!err_empty(ball_default_y_point,	"ball_default_y_point")		? valid_check	= false : "" ;
			!err_empty(ball_default_x_point,	"ball_default_x_point")		? valid_check	= false : "" ;
			!err_empty(ball_direction_patern,	"ball_direction_patern")	? valid_check	= false : "" ;
			!err_empty(ball_move_y_point,			"ball_move_y_point")			? valid_check	= false : "" ;
			!err_empty(ball_move_x_point,			"ball_move_x_point")			? valid_check	= false : "" ;
			!err_empty(ball_move_count,				"ball_move_count")				? valid_check	= false : "" ;

			// 空文字チェックがNGならその時点で処理終了。エラー表示。 
			if(!valid_check){
				err_msg = "項目を全て入力して下さい" ;
			}
			// 空文字チェックがOKなら、１項目ずつエラー判定（半角数値かどうか）
			// 途中でエラーが発生すればその時点で処理終了。エラー表示。
			if(!err_numeric(field_y_point, "field_y_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(field_x_point, "field_x_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_default_y_point, "ball_default_y_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_default_x_point, "ball_default_x_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_direction_patern, "ball_direction_patern") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_move_y_point, "ball_move_y_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_move_x_point, "ball_move_x_point") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}
			if(!err_numeric(ball_move_count, "ball_move_count") && valid_check){
				err_msg			= "半角数値で入力して下さい";
				valid_check	= false;
			}

			if(!err_field_limit(ball_default_y_point, field_y_point, "ball_default_y_point") && valid_check){
				err_msg			= "ボールの初期位置はフィールドの上限内に設定して下さい";
				valid_check	= false;
			}
			if(!err_field_limit(ball_default_x_point, field_x_point, "ball_default_x_point") && valid_check){
				err_msg			= "ボールの初期位置はフィールドの上限内に設定して下さい";
				valid_check	= false;
			}

			if(field_y_point == 1){
				if(ball_direction_patern != 3 || ball_direction_patern != 4 ){
					err_msg			= "ボールの移動方向はフィールドに合わせた方向にして下さい（右もしくは左を入力して下さい）";
					valid_check	= false;
				}
			}
			if(field_x_point == 1){
				if(ball_direction_patern != 1 || ball_direction_patern != 6 ){
					err_msg			= "ボールの移動方向はフィールドに合わせた方向にして下さい（上もしくは下を入力して下さい）";
					valid_check	= false;
				}
			}

			// 上記バリデーションが、全部OKならフォームを送信する。NGならエラーメッセージを挿入する。
			if(valid_check ){ 
				form_tag.submit() ;
			}else{
				err_tag.innerText = err_msg;
				document.getElementById("field").style.display = "none";
			}
		}
		// フィールドの閾値が設定された時にボール初期位置のMax値（Y軸：縦）に自動設定
		function get_field_y_point(){
			var field_y_point	= document.getElementById("field_y_point").value;
			var input_tag			= document.getElementById("ball_default_y_point");
			input_tag.setAttribute("max", field_y_point);
		}
		// フィールドの閾値が設定された時にボール初期位置のMax値（X軸：横）に自動設定
		function get_field_x_point(){
			var field_x_point	= document.getElementById("field_x_point").value;
			var input_tag			= document.getElementById("ball_default_x_point");
			input_tag.setAttribute("max", field_x_point);
		}
		// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　関数（ローカルファイル内の共通関数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

		/* 以降、正規表現関数 */
		//【関数】空文字判定
		function err_empty(val, id){
			if(val == ""){
				err_input_addcss(id);
				return false;
			}else{
				err_input_removecss(id);
				return true;
			}
		}
		//【関数】半角数値判定
		function err_numeric(val, id){
			if(!val.match(/^[0-9]*$/)){
				err_input_addcss(id);
				return false;
			}else{
				err_input_removecss(id);
				return true;
			}
		}
		//【関数】フィールド内の上限判定
		function err_field_limit(val, limit, id){
			val		= parseInt(val);
			limit	= parseInt(limit);
			if(val > limit){
				err_input_addcss(id);
				return false;
			}else{
				err_input_removecss(id);
				return true;
			}
		}
		//【関数】エラーがあるInputタグにClassを追加する。
		function err_input_addcss(id){
			document.getElementById(id).classList.add("err_input");
		}
		//【関数】エラーが解消（ない）InputタグからClassを除去する。
		function err_input_removecss(id){
			document.getElementById(id).classList.remove("err_input");
		}
	</script>
	<?= $move_ball_js ?>
	<style>
		.err{
			color : red;
		}
		.err_input{
			border : solid 3px red; 
		}
		body{
			margin			: auto;
			text-align	: center;
		}
		input,select{
			height		: 20px;
			width			: 80%;
			font-size : 1.1em;
		}
		input:hover{
			background : pink;
		}
		select:hover{
			background	: pink;
			font-size		: 1em;
		}

		/* 設定画面のCSS */
		.setting_table{
			margin			: auto;
			text-align	: center;
		}
		#setting th,
		#setting td{
			padding : 10px;
		}
		#setting td{
			text-align	: right;
		}
		#ball_direction_patern{
			text-align	: right;
			width				: 80%;
		}
		.setting_submit{
			text-align		: center;
			height				: 50px;
			width 				: 100%;
			background		: lightgray;
			border-radius : 30px;
			color					: white;
			font-size			: 2em;
		}

		/* フィールド画面のCSS */
		.field{
			margin			: auto;
			text-align	: center;
		}
		.table{
			margin					: auto;
			border-collapse	: collapse;
			border					: solid 1px black;
		}
		.table th{
			height				: 30px;
			width					: 30px;
			text-align		: center;
			padding-left	: 5px;
		}
		.table td{
			border				: solid 1px black;
			height				: 30px;
			width					: 30px;
			text-align		: center;
			padding-left	: 5px;
		}
		#ball_move_speed{
			width		: 50px;
		}
		.move_start{
			font-size					: 3em;
			height						: 100px;
			width							: 800px;
			background-color	: skyblue ;
			border-radius			: 30px;
			transition 				: 0.5s;
		}
		.move_start:hover{
			font-size					: 4em;
			color 						: white;
			transition 				: 0.5s;
		}
	</style>
</head>
<body>
	<h1>ボールの軌道シミュレーション</h1>
	<section class="display_container">
		<h2></h2>
		<section>
			<!-- エラー表示枠 -->
			<section id="setting_err" class="err">
				<?php
				if(!is_null($err_msg) && $patern == "insert"){
					echo $err_msg;
				} ?>
			</section>
			<br>
			<section>
				<h3>ボールの軌道予想の諸設定</h3>
				<form id="setting" action="<?= $source_name ?>" method="POST">
					<table class="setting_table">
						<tr>
							<th></th>
							<th>縦（Y軸）</th>
							<th>横（X軸）</th>
						</tr>
						<tr>
							<th>フィールドの設定</th>
							<td>
								<input type="number" id="field_y_point" name="field_y_point" value="<?= h($field_y_point) ?>" min="1" plasehorder="数値を入力してください" onchange="get_field_y_point()">
							</td>
							<td>
								<input type="number" id="field_x_point" name="field_x_point" value="<?= h($field_x_point) ?>" min="1" plasehorder="数値を入力してください" onchange="get_field_x_point()">
							</td>
						</tr>
						<tr>
							<th>ボールの初期位置</th>
							<td>
								<input type="number" id="ball_default_y_point" name="ball_default_y_point" value="<?= h($ball_default_y_point) ?>" min="1" plasehorder="数値を入力してください">
							</td>
							<td>
								<input type="number" id="ball_default_x_point" name="ball_default_x_point" value="<?= h($ball_default_x_point) ?>" min="1" plasehorder="数値を入力してください">
							</td>
						</tr>
						<tr>
							<th>ボールの移動方向</th>
							<td>
								<select id="ball_direction_patern" name="ball_direction_patern" >
									<?php 
										foreach($ball_direction_list as $key => $val){
											// POST送信時は前回選んだ項目と同じものを選択済みにする
											if(!empty($ball_direction_patern) && $ball_direction_patern == $key){
												echo "<option value=\"$key\" selected>" , $val , "</option>";
											}else{
												echo "<option value=\"$key\">" , $val , "</option>"; 
											}
										}
									?>
								</select>
							</td>
							<td></td>
						</tr>
						<tr>
							<th>1回の移動マス</th>
							<td>
								<input type="number" id="ball_move_y_point" name="ball_move_y_point" value="<?= h($ball_move_y_point) ?>" min="1" plasehorder="数値を入力してください">
							</td>
							<td>
								<input type="number" id="ball_move_x_point" name="ball_move_x_point" value="<?= h($ball_move_x_point) ?>" min="1"  plasehorder="数値を入力してください">
							</td>
						</tr>
						<tr>
							<th>ボールを動かす回数</th>
							<td>
								<input type="number" id="ball_move_count" name="ball_move_count" value="<?= h($ball_move_count) ?>" min="1" plasehorder="数値を入力してください">
							</td>
							<td></td>
						</tr>
						<tr>
							<td colspan="3">
								<input type="hidden" name="ball_move_speed" value="">
								<input type="button" class="setting_submit" value="送信"	onclick="err_check('setting')">
							</td>
						</tr>
					</table>
				</form>
			</section>
			<?= $delimiter ?>

			<section id="field" class="field">　
				<?= $field ?>
				<form action="<?= $source_name ?>" method="POST">
					<?= $hidden_tags ?>
					<?=	$ball_move_contents ?>
				</form>
			</section>
		</section>
	</section>
	<br><br><br>
</body>
</html>
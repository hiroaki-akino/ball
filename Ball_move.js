/*
=============================================
作成者：秋野浩朗
作成日：2020/7/13
修正日：----/-/-
概要　：ボールの軌道予想
備考　：PHPの処理を全く同じ形でJSに変換したやつ。おまけなので処理は若干雑
=============================================

■ 参考URL
JS submit()の留意点　https://techblog.recochoku.jp/5887
JS キャスト(DOM操作時は全て文字列で取得される？)　https://blog.goo.ne.jp/kori39/e/03d9ce6a3180e9a553644a763fc42542
*/ 


// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　変数（ローカルファイル内の共通変数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝


// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　関数（ローカルファイル内の共通関数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

window.onload = function(){
	var field_y_point							= parseInt( document.getElementById("field_y_point").value);							// フィールドの最大縦マス数（数値）
	var field_x_point							= parseInt( document.getElementById("field_x_point").value);							// フィールドの最大横マス数（数値）
	var ball_default_y_point			= parseInt( document.getElementById("ball_default_y_point").value);				// ボールの初期位置の縦マス数（数値）
	var ball_default_x_point			= parseInt( document.getElementById("ball_default_x_point").value);				// ボールの初期位置の横マス数（数値）
	var ball_direction_patern			= parseInt( document.getElementById("ball_direction_patern").value);			// 移動方向パターン（数値）
	var ball_move_y_point					= parseInt( document.getElementById("ball_move_y_point").value);					// １回の移動でボールが縦に進むマス数（数値）
	var ball_move_x_point					= parseInt( document.getElementById("ball_move_x_point").value);					// １回の移動でボールが横に進むマス数（数値）
	var ball_move_speed						= parseFloat( document.getElementById("ball_move_speed").value ) * 1000;	// ボールが動くスピード（秒をsecで扱う）（小数点）
	var ball_current_y_point			=	ball_default_y_point;	// ボールの現在位置の縦マス数（数値）
	var ball_current_x_point			=	ball_default_x_point;	// ボールの現在位置の横マス数（数値）
	var ball_current_direction_y	= "";										// ボールの現在の縦方向の向き（true:down  / false:up ）
	var ball_current_direction_x	=	"";										// ボールの現在の横方向の向き（true:right / false:left ）
	var ball_default_y_on_border	= false;								// ボールの初期位置が枠沿いかどうか（true:枠沿い / false:それ以外）
	var ball_default_x_on_border	= false;								// ボールの初期位置が枠沿いかどうか（true:枠沿い / false:それ以外
	var ball_default_y_on_corner	= false;// ボールの初期位置が四隅かどうか（true:四隅 / false:それ以外）
	var ball_default_x_on_corner	= false;// ボールの初期位置が四隅かどうか（true:四隅 / false:それ以外)
	/*【関数】最初のボールの向きを決める */
	function default_direction(ball_direction_patern){
		switch(ball_direction_patern){
			// 左上方向への移動の場合
			case 0 :
				ball_current_direction_y = false;
				ball_current_direction_x = false;
				break;
			// 真上方向への移動の場合
			case 1 :
				ball_current_direction_y = false;
				ball_current_direction_x = null;
				break;
			// 右上方向への移動の場合
			case 2 :
				ball_current_direction_y = false;
				ball_current_direction_x = true;
				break;
			// 左方向への移動の場合
			case 3 :
				ball_current_direction_y = null;
				ball_current_direction_x = false;
				break;
			// 右方向への移動の場合
			case 4 :
				ball_current_direction_y = null;
				ball_current_direction_x = true;
				break;
			// 左下方向への移動の場合
			case 5 :
				ball_current_direction_y = true;
				ball_current_direction_x = false;
				break;
			// 真下方向への移動の場合
			case 6 :
				ball_current_direction_y = true;
				ball_current_direction_x = null;
				break;
			// 右下方向への移動の場合
			case 7 :
				ball_current_direction_y = true;
				ball_current_direction_x = true;
				break;
		}
	}

	/*【関数】ボールの初期値が枠線上がどうかを判定 */
	// 引数：フィールドの閾値、ボールの初期位置、ボールの方向
	// 備考：ボールの初期位置が枠沿いの時はいきなり反射するので前処理が必要
	function default_on_border(f_y, f_x, d_y, d_x, d_p){
		// ボールのY軸（縦）の初期位置が枠沿いの時
		if( d_y == f_y || d_y == 1 ){
			ball_default_y_on_border = true;
		}
		// ボールのX軸（横）の初期位置が枠沿いの時
		if( d_x == f_x || d_x == 1 ){
			ball_default_x_on_border = true;
		}
	}
	
	/*【関数】ボールの初期値が四隅がどうかを判定 */
	// 引数：フィールドの閾値、ボールの初期位置
	// 備考：ボールの初期位置が四隅の時はいきなり真逆に反射するので前処理が必要
	function default_on_corner(f_y, f_x, d_y, d_x, d_p){
		// ボールの初期位置が四隅の何れかで、最初の向きが四隅に対して真逆になる時
		if( (d_y == 1		&& d_x == 1		&& d_p == 0)	||	// 初期位置：左上、初期方向：左上
				(d_y == 1		&& d_x == f_x && d_p == 2)	||	// 初期位置：右上、初期方向：右上
				(d_y == f_y	&& d_x == 1		&& d_p == 5)	||	// 初期位置：左下、初期方向：左下
				(d_y == f_y && d_x == f_x	&& d_p == 7) ){	// 初期位置：右上、初期方向：右上
				ball_default_y_on_corner = true;
				ball_default_x_on_corner = true;
		}
	}

	/*【関数】次のボールのY（縦）位置を決める */
	// 引数：フィールドの最大Y値、ボールの初期値、現在のボールの位置、ボールが１回に動くマス数、初期値が枠線上かどうかのフラグ、ボールを動かす回数
	// 返値：次にボールが動くY（縦）位置
	function next_y_point(f_y, d_y, c_y, m_y, on_border, on_corner, count){
	
		// Y軸が動かない時（左方向のみor右方向のみの移動）
		if(ball_current_direction_y == null){
			result = c_y;
			return result;
		}
		// Y軸が動く時（上記以外の移動）ボールが１回で動く位置を算出
		for(var i = 1 ; i <= m_y ; i++){
			// 初期値が枠線上で、初動の場合
			if(on_border && count == 1){
				flug = true;
				// 初期値が枠の上限上で、ボールの向きが上向きで、ボールの移動が１マス目（i=1）の時（いきなり反射する）
				if(d_y == 1 && !ball_current_direction_y && i == 1){
					// 現在のボールの向きを下向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
					ball_current_direction_y = true;
					// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
					if(on_corner){
						c_y++;
					}
					flug = false;
				}
				// 初期値が枠の下限上で、ボールの向きが下向きで、ボールの移動が１マス目（i=1）の時時（いきなり反射する）
				if(d_y == f_y && ball_current_direction_y && i == 1){
					// 現在のボールの向きを上向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
					ball_current_direction_y = false;
					// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
					if(on_corner){
						c_y--;
					}
					flug = false;
				}
				// 上記以外の時（ボールの移動が２マス目以降の時）
				if(flug){
					if(!ball_current_direction_y){
						// ボールの現在の向きが上向きの時
						// 現在のボールの位置を移動（１マス減らす）
						c_y--;
					}else{
						// ボールの現在の向きが下向きの時
						// 現在のボールの位置を移動（１マス増やす）
						c_y++;
					}
				}
			}else{
			// 初期値が枠線上以外かつ、初動ではない時
				switch(true){
					// 現在のボールの位置が枠の上限上の時
					case c_y == 1 :
						// 現在のボールの向きを下向きに変える
						ball_current_direction_y = true;
						// 現在のボールの位置を移動（１マス増やす）
						c_y++;
						break;
					// 現在のボールの位置が枠の下限上の時
					case c_y == f_y :
						// 現在のボールの向きを上向きに変える
						ball_current_direction_y = false;
						// 現在のボールの位置を移動（１マス減らす）
						c_y--;
						break;
					// 現在のボールが枠線以外の時
					default :
						if(!ball_current_direction_y){
							// ボールの現在の向きが上向きの時
							// 現在のボールの位置を移動（１マス減らす）
							c_y--;
						}else{
							// ボールの現在の向きが下向きの時
							// 現在のボールの位置を移動（１マス増やす）
							c_y++;
						}
				}
			}
			result = c_y;
			// console.log(i + ":" + ball_current_direction_y + ":" + result);
		}
		return result;
	}
	
	/*【関数】次のボールのX（横）位置を決める */
	// 引数：フィールドの最大X値、ボールの初期値、現在のボールの位置、ボールが１回に動くマス数、初期値が枠線上かどうかのフラグ、ボールを動かす回数
	// 返値：次にボールが動くX（横）位置
	function next_x_point(f_x, d_x, c_x, m_x, on_border, on_corner, count){
	
		// Y軸が動かない時（左方向のみor右方向のみの移動）
		if( ball_current_direction_x == null){
			result = c_x;
			return result;
		}
		// Y軸が動く時（上記以外の移動）ボールが１回で動く位置を算出
		for(var i = 1 ; i <= m_x ; i++){
			// 初期値が枠線上で、初動の場合
			if(on_border && count == 1){
				flug = true;
				// 初期値が枠の上限上で、ボールの向きが左向きで、ボールの移動が１マス目（i=1）の時（いきなり反射する）
				if(d_x == 1 && !ball_current_direction_x && i == 1){
					// 現在のボールの向きを右向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
					ball_current_direction_x = true;
					// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
					if(on_corner){
						c_x++;
					}
					flug = false;
				}
				// 初期値が枠の下限上で、ボールの向きが右向きで、ボールの移動が１マス目（i=1）の時時（いきなり反射する）
				if(d_x == f_x && ball_current_direction_x && i == 1){
					// 現在のボールの向きを左向きに変える（初回マスの時だけ変えないと２マス目以降の処理がずれる）
					ball_current_direction_x = false;
					// 四隅の時は真逆に反射するので現在のボール位置を移動する（それ以外の反射では移動しない）
					if(on_corner){
						c_x--;
					}
					flug = false;
				}
				// 上記以外の時（ボールの移動が２マス目以降の時）
				if( flug ){
					if(!ball_current_direction_x){
						// ボールの現在の向きが左向きの時
						// 現在のボールの位置を移動（１マス減らす）
						c_x--;
					}else{
						// ボールの現在の向きが右向きの時
						// 現在のボールの位置を移動（１マス増やす）
						c_x++;
					}
				}
			}else{
			// 初期値が枠線上以外かつ、初動ではない時
				switch(true){
					// 現在のボールの位置が枠の上限上の時
					case c_x == 1 :
						// 現在のボールの向きを下向きに変える
						ball_current_direction_x = true;
						// 現在のボールの位置を移動（１マス増やす）
						c_x++;
						break;
					// 現在のボールの位置が枠の下限上の時
					case c_x == f_x :
						// 現在のボールの向きを上向きに変える
						ball_current_direction_x = false;
						// 現在のボールの位置を移動（１マス減らす）
						c_x--;
						break;
					// 現在のボールが枠線以外の時
					default :
						if(!ball_current_direction_x){
							// ボールの現在の向きが上向きの時
							// 現在のボールの位置を移動（１マス減らす）
							c_x--;
						}else{
							// ボールの現在の向きが下向きの時
							// 現在のボールの位置を移動（１マス増やす）
							c_x++;
						}
				}
			}
			result = c_x;
			// echo "i:",var_dump(ball_current_direction_x),":result<br>";
		}
		return result;
	}


	var i = 1;
	// ボールボールの軌道（X/Y軸の位置）を算出
	var move_ball = function move_ball(){
		// 初回以外は現在表示されているボール（過去のボール）を書き換える
		if(i != 1){
			remove_ball(ball_current_y_point, ball_current_x_point);
		}

		// 算出した値を現在のボール位置に格納
		ball_current_y_point = parseInt( next_y_point(field_y_point, ball_default_y_point, ball_current_y_point, ball_move_y_point, ball_default_y_on_border, ball_default_y_on_corner, i) );
		ball_current_x_point = parseInt( next_x_point(field_x_point, ball_default_x_point, ball_current_x_point, ball_move_x_point, ball_default_x_on_border, ball_default_x_on_corner, i) );

		// 算出した結果を軌道結果配列に格納
		var next_point_id = ball_current_y_point + "-" + ball_current_x_point
		//console.log(next_point_id);
		document.getElementById(next_point_id).innerHTML = "🔴";
		i++;
	}

	// 指定のidのボールを書き換える
	function remove_ball(c_y, c_x){
		// ただし初期値のマスのボールは変えない。
		// console.log("aa" + ball_default_y_point + ball_default_x_point);
		if(c_y != ball_default_y_point || c_x != ball_default_x_point){
			var next_point_id = c_y + "-" + c_x;
			document.getElementById(next_point_id).innerHTML= "●";
		}
	}

	// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　以降、各種処理　＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝


	// 初期値のボールを表示
	var default_point_id = ball_default_y_point + "-" + ball_default_x_point
	document.getElementById(default_point_id).innerHTML = "🔴";

	// 入力された値からボールの最初の向きを決定
	default_direction(ball_direction_patern);

	// ボールの初期位置が枠沿いかどうかの判定
	default_on_border(field_y_point, field_x_point, ball_default_y_point, ball_default_x_point, ball_direction_patern);

	// ボールの初期位置が四隅かどうかの判定
	default_on_corner(field_y_point, field_x_point, ball_default_y_point, ball_default_x_point, ball_direction_patern);

	setInterval(move_ball, ball_move_speed);
}
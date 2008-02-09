<?php
  /* 
   各種設定はconf.inc.phpで行ってください。
  */
define( 'BBS_DIR', PLUGIN_DIR . '2chBBS/' );

//function init( $args ) {
function init() {
    if( 1<func_num_args() ){
        return "引数は1つにして下さい";
    }
    $args=array_shift( func_get_args() );

    if( $args != '' && $args != '2chBBS'  ){
        $itaName = '2chBBS/' . $args;
    }
    else{
        $itaName = '2chBBS/';
    }
    require_once( PLUGIN_DIR . '2chBBS/display.inc.php' );
    require_once( PLUGIN_DIR . $itaName . '/conf.inc.php' );
    //require_once(PLUGIN_DIR . 'googlead_banner.inc.php');
    
    define( 'SUBJ_FILE' , PLUGIN_DIR . $itaName . '/subject.txt' );
    define( 'SUBB_FILE' , PLUGIN_DIR . $itaName . '/subback.txt' );
    define( 'LASTSIZE'  , PLUGIN_DIR . $itaName . '/lastsize.txt' );
    define( 'DAT_DIR'   , PLUGIN_DIR . $itaName . '/dat/' );
}
function plugin_bbs_convert() {
	global $post, $get;
 
	init();

	if($post[com] != ''){
		$p = post($post[sub], $post[name], $post[com], $post[email], $post[kdate]);
		return $p;
	}
	$bbs = new bbsDisplay();
	/* １スレッド表示 */
	if ( $get[ 'mode' ] == 'thread' ){
		$str .= '<div id="bbs">';
		$str .= $bbs->getThread( $get[ 'subj_num' ] );
		$str .= '</div>';
		return $str;
	}
	/* スレッドタイトル一覧表示 */
	elseif ( $get[ 'mode' ] == 'subjectlist' ){
		return $bbs->getAllSubjectList();
	}
	/* 管理モード */
	elseif ( $get[ 'mode' ] == 'admin' ){
		if ( !isset( $get[ 'thread' ] )){
			return $bbs->getAdminMode();
		}
		else{
			return $bbs->getResDelete( $get[ 'thread' ] );
		}
	}
	/* 標準表示 */
	else {
		return $bbs->getShow();
	}
}

function post($sub, $name, $com, $email, $kdate){
	global $script, $vars;
	global $notify, $notify_diff_only, $notify_to, $notify_subject, $notify_header;
	global $smtp_server, $smtp_auth;

	
	if( BANASCII == 1 ){
	  $byte = strlen( $com );
	  $len= mb_strlen( $com );
	  
	  if($byte == $len){ return errorMsg("英数字だけでは書き込めません。"); }
	}
	if( NGWORDmode==1 && mbereg( NGWORD, $com )){
	    return errorMsg( "NG Wordが含まれています。" );
	}
	
	
	$page = rawurlencode( $vars['page'] );
	
	function CleanStr($u_str){
		if(get_magic_quotes_gpc()) $u_str = stripslashes($u_str);
		$u_str = htmlspecialchars($u_str);
		return str_replace("<>", "&#44;", $u_str);
	}
	if(ereg("^( |　|\t)*$",$com)){ return errorMsg("本文が書き込まれていません"); }
	if(ereg("^( |　|\t)*$",$name)){ $name = NANASHI; }
	if($key=="" && (ereg("^( |　|\t)*$",$sub))){ return errorMsg("タイトルが書き込まれていません"); }
	

	$sub   = CleanStr($sub);
	$name  = CleanStr($name);
	$name  = ereg_replace("[\r\n]", "", $name);
	//$com   = CleanStr($com);  /* プラグイン展開が出来なくなる */
	$email = CleanStr($email);
	$now = gmdate("Y/m/d H:i", time()+9*3600);

	$com  = make_link($com, $page);

	/* ID */
	$id = getID();

	/* 改行文字の統一 */
	$com  = str_replace( "\n", "", $com);
	$com  = str_replace( "\r",  "<br />", $com);
	$com  = str_replace( "\r\n",  "<br />", $com);
	
	if( $sub ) { $key = time(); }

	
	/* レスポンスアンカー */
	$com = preg_replace("/&gt;&gt;(\d+)-(\d+)/si", "<a href=\"$script?cmd=read&page=$page&mode=thread&kdate=$kdate&st=\\1&to=\\2\">&gt;&gt; \\1-\\2</a>", $com);
	$com = preg_replace("/&gt;&gt;(\d+)/si", "<a href=\"$script?cmd=read&page=$page&mode=thread&kdate=$kdate&st=\\1&to=\\1\">&gt;&gt;\\1</a>", $com);
	Cookie( $name, $email );

	/* トリップ */
	$name = getTrip( $name );


	/* 多重カキコチェック */
	//$last = fopen(LASTSIZE, "r+");
	//$lsize = fread($last, filesize(LASTSIZE));
	$last = file( LASTSIZE );
	for($i = 0; $i < count($last); $i++){
		list($lname, $lcom) = explode(SPL, $last[$i]);
		if( $name==$lname && $com==$lcom ){
			return errorMsg( "多重書き込みです。" );
		}
	}
	//rewind($last);
	array_unshift( $last, "$name".SPL."$com\n" );
	//fputs($last, "$name\t$com\t");
	//fclose($last);
	for( $i=0; $i<LAST; $i++ ){
		fputs( $lsize, $last[$i] );
	}
	fclose( $lsize );

	$host = md5( $host );

	// 記事フォーマット
	$newlog = $name.SPL.$email.SPL.$now.SPL.$com.SPL.$sub.SPL.$host.SPL.$id."\n";
	// スレ一覧読み込む
	$subj_arr = file(SUBB_FILE);

	// 親スレ投稿の場合、ファイル作成
	if( $sub != 'res' ){
		// 既にある場合（time()）は1増やす（意味無いかも
		if(file_exists(DAT_DIR . $key . '.dat')) $key++;
		$fp = fopen(DAT_DIR . $key . '.dat', "w");
		fputs($fp, $newlog);
		fclose($fp);
		// ﾊﾟｰﾐｯｼｮﾝ666にする
		@chmod(DAT_DIR . $key . '.dat', 0666);

		// スレ一覧の先頭に加える
		$new_subj = "$key.dat<>$sub(1)\n";
		array_unshift($subj_arr, $new_subj);
	}
	// レス投稿の場合
	else{
		$key = $kdate;
		// レスファイル名
		$resfile = DAT_DIR . $key;
		if(!file_exists($resfile)) { return errorMsg("スレッドが見つかりません"); }
		// 該当レス読み込む
		$res_arr = file($resfile);
		// レス数取得
		$resline = sizeof($res_arr);
		// ｶｳﾝﾄアップ
		$resline++;

		/*
		// レス数制限オーバー
		if($resline > $numlimit)
		@chmod($resfile, 0444);
		
		if($resline == $numlimit){
			$nextnum = $numlimit + 1;
			$com = "[color=red]このスレッドは $numlimit を超えました<br />もう書けないので新しいスレッドを立てて下さい[/color]";
			$newlog.= "$nextnum<><>Over $numlimit Thread<>$com,\n";
			$resline = $nextnum;
		}
		*/
		
		// レス書き込み
		//if (!is_writable($resfile)) error('このスレッドには書込めません');

		$re = @fopen($resfile, 'a') or errorMsg("このスレッドには書込めません");

		fputs($re, $newlog);
		fclose($re);


		// レスｶｳﾝﾄアップ
		for ($r=0; $r<count($subj_arr); $r++) {
			list($kdate,$title) = explode("<>", $subj_arr[$r]);
			
			/*レス数を抽出　もっとちゃんとした方法があるはず...
			list($title, $rescnt) = explode('(', $title);
			list($rescnt, ) = explode(')', $res);
			*/

			$title = ereg_replace("\([0-9]*\)\n$", "", $title);
			
			if ( $kdate == $key ) {
				if ($email == "sage") {
					$subj_arr[$r] = "$kdate<>$title($resline)\n";
				} 
				else{
					array_unshift($subj_arr, "$kdate<>$title($resline)\n");
					array_splice($subj_arr, $r+1, 1);
				}
			}
		}
	}
	// subback.txt更新、全部
	$bf = fopen( SUBB_FILE, "w" );
	flock( $bf, 2 );
	reset( $subj_arr );
	fputs( $bf, implode('',$subj_arr) );
	fclose( $bf );

	// subject.txt更新、一定数に収める
	$sf = fopen( SUBJ_FILE, "w" );
	flock( $sf, 2 );
	for( $i=0; $i<THR_NUM; $i++ ){
		fputs( $sf, $subj_arr[$i] );
	}
	fclose( $sf );

	touchtimestamp();
	
	// email notify
	if( NOTIFY==1 ){
		$str = $newlog;
		if ($notify) {
			if ($smtp_auth) {
				pop_before_smtp();
			}
			$subject = str_replace('$page', '掲示板', $notify_subject);
			$str = str_replace('<>', "\r\n\r\n", $str);
			ini_set('SMTP', $smtp_server);
			mb_language(LANG);
			mb_send_mail($notify_to, $subject, $str, $notify_header);
		}
	}

	return errorMsg("書き込めました。");
}
function getTrip( $name ) {
	if( strstr( $name, '◆' )){
		$name = $name . 'トリップ詐称';
	}
	elseif( strstr( $name, "#" )){
		$pass = substr($name, strpos($name,"#")+1);
		$pass = str_replace('&#44;',',',$pass);
		$salt = substr($pass."H.", 1, 2);
		$salt = ereg_replace("[^\.-z]", ".", $salt);
		$salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
		$name2 = substr($name, 0, strpos($name,"#"));
		$name = $name2 . ' <b>◆' . substr(crypt($pass,$salt),-8) . '</b>';
	}
	return $name;
}

function getID()
{
	global $script, $vars;

	$host = gethostbyaddr(getenv("REMOTE_ADDR"));
	$idnum = str_replace(".", "", $host);
	$bbscrypt = ord($script);
	
	//$idcrypt = substr(crypt(($bbscrypt+$idnum),gmdate("d",time()+9*3600)),-8);
	//$idcrypt = substr(crypt($idnum+$bbscrypt), 0, 8);
	//$id = $idcrypt;

	$ymd = gmdate("ymd", time()+9*3600); // 6文字の年月日
	$salt = substr(md5($idnum . $ymd), 0, 2); // ホスト名と日時を元に saltを作成
	$idcrypt = substr(crypt($bbscrypt . $idnum . $ymd, $salt), 0, 8);

	//$salt = gmdate( "m", time()+9*3600 ) . gmdate( "d", time()+9*3600 );
	//$salt = substr( md5($salt), 0, 2 );
	//$id = crypt(( $idnum . $bbscrypt ), $salt );
	//$id = substr( $id, 0, 8 );
	
	return $idcrypt;
}
function Cookie( $name, $email )
{
	$cookval = implode( SPL, array($name,$email) );
	setcookie( BBS_NAME, $cookval, time()+30*24*3600 );
}

//タイムスタンプ更新
function touchtimestamp()
{
	touch( get_filename( $GLOBALS['vars']['page'] ));
	put_lastmodified();
}
function errorMsg( $msg )
{
	global $vars, $script;
	
	$str  = '<center>';
	$str .= '<div id="bbs_error">';
	$str .= $msg;
	$str .= '<br /><a href="' . $script . '?cmd=read&page=' . rawurlencode($vars['page']) . '">' . '掲示板に戻る</a>';
	$str .= '</div>';
	$str .= '</center>';
	return $str;
}
?>
<?php
/* 掲示板の名前(Cookieに使用) */
define( 'BBS_NAME', 'PukiwikiBBS' );

/* 1スレッドあたりのレス上限 */
define( 'RES_NUM', '1000' );

/* スレッド数の上限 */
define( 'THR_NUM', '40' );

/* ヘッドライン枠内表示数 */
define( 'HEADLINE_NUM', '10' );

/* スレッド一覧の１スレッドあたりのレス表示数 */
define( 'LS', '7' );

/* ログファイルの区切り文字 */
define( 'SPL', '<>' );

/*
直近の書き込み内容保存数
多重書き込み対策
*/
define( 'LAST', '20' );

/* ASCIIのみの書き込みを拒否する 0=拒否しない */
define( 'BANASCII', 0 );

/* NG Wordを正規表現で指定
 * コメントにabcが含まれている場合に拒否する 
 * define( 'NGWORD', 'abc' );
 */
define( 'NGWORDmode', 0 ); /* 0=NG word機能を無効にする */
define( 'NGWORD', '' );

/* 名無しの名前 */
define( 'NANASHI', '名無しさん' );

/* IDを表示するがどうか 0=表示しない*/
define( 'IDmode', '1' );

/* 書き込みをemailで通知するかどうか 0=しない 
 * Pukiwiki本体のemail notify機能を利用します。
 */
define( 'NOTIFY', '0' );
?>
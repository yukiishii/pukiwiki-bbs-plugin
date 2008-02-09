<?php
class bbsDisplay{
  var $subject;
	
  /*
   �������
  */
  function bbsDisplay()
  {
    if( @file( SUBJ_FILE ))
      $this->subject = @file( SUBJ_FILE );
  }
	
  /*
   ɸ����̼���
  */  
  function getShow()
  {
    $ret  = '<div id="bbs">';
    $ret .= $this->getSubjectList();
    $ret .= $this->getThreadList();
    $ret .= $this->getNewThreadForm();
    $ret .= $this->getLicense();
    $ret .= '</div>';
    return $ret;
  }
  /*
   ����åɰ���
  */
  function getThreadList()
  {
    global $vars;
    $subject = $this->subject;

    for($i = 0; ( $i < HEADLINE_NUM ) && ( $i < count($subject) ); $i++){
      list($kdate, $title) = explode(SPL, $subject[$i]);
			
      $log = @file( DAT_DIR . $kdate );
			
      $str .= '<div class="bbs_thread"><a name="'.($i+1).'"></a><div align="right"><a href=#subject>��</a><a href=#'.($i+2).'>��</a><a href=#'.($i).'>��</a></div>';
      $str .= $this->getThreadContents( $i, '', '', LS);
      $str .= '</div>';
    }
    return $str;
  }
  /*
   1����å�ɽ��
  */
  function getThread( $subjectNum )
  {
    global $script, $vars, $get;
    $subject = $this->subject;
    list($kdate, $title, $res) = explode(SPL, $subject[$subjectNum]);

    $log = @file(DAT_DIR . $kdate);
    $res = count($log);

    $str .= '<div id="bbs">';
    $str .= '<div class="bbs_thread">';
	$str .= '<a href = "' . $this->getPageName() . '">����������뢣</a>';
    $str .= $this->getThreadContents( $get['subj_num'], $get['st'], $get['to'], $get['ls'] );
    $str .= '</div>';
    $str .= '</div>';
    $str .= $this->getLicense();

    return $str;
  }
  /*
   ����⡼��
  */
  function getAdminMode()
  {
    global $script, $vars;
    $subject = $this->subject;

    $str  = '<div id="bbs_delete">';
    $str .= '<table border="1" cellspacing="0" cellpadding="5">';
    $str .= '<tr>';
    $str .= '<th>����å�̾</th>';
    $str .= '<th>�쥹��</th>';
    $str .= '<th>����쥹�õ�</th>';
    $str .= '</tr>';
    for( $i = 0; $i < count($subject); $i++ ) {
      $str .= '<tr>';
      list($kdate, $title) = explode(SPL, $subject[$i]);
      list($title, $res) = explode('(', $title);
      list($res, ) = explode(')', $res);
	  //$res=$this->getResCount( $kdate );
	
      $str .= '<td>' . $title . '</td>';
      $str .= '<td>' . $res . '</td>';
      $str .= '<td><a href="' . $this->getPageName() . '&mode=admin&thread=' . $kdate . '">�������</a></td>';
      $str .= '</tr>';
    }
    $str .= '</table>';
    $str .= '</div>';
		
    return $str;
  }
  function getResDelete( $kdate )
  {
    $log = @file(DAT_DIR . $kdate);
		
    $str .= '<table border="1" cellspacing="0" cellpadding="5"><tr>';
    $str .= '<th>�����å�</th>';
    $str .= '<th>�쥹�ֹ�</th>';
    $str .= '<th>̾��</th>';
    $str .= '<th>��ʸ</th>';
    $str .= '</tr>';

    for( $i = 0; $i < count( $log ); $i++ ){
      list($fname,$femail,$fdate,$fcom,$fsub,$fhost,$fid) = explode("<>", $log[$i]);
      $str .= '<tr>';
      $str .= '<td><input type="checkbox" name="' . $i . '" /></td>';
      $str .= '<td>' . ($i + 1) . '</td>';
      $str .= '<td>' . $fname . '</td>';
      $str .= '<td>' . $fcom . '</td>';
      $str .= '</tr>';
    }
    $str .= '</table>';
    return $str;
  }
  /*
   �쥹�ե�����
  */
  function getResForm( $kdate, $subjectNum )
  {
    global $script, $vars;
		
    //���å���Ÿ��
    list( $cname, $cmail ) = explode( SPL, $_COOKIE[ BBS_NAME ] );

    $str .= '<div class="bbs_res_form">';
    $str .= '<form method="post" action="' . $this->getPageName()  . '"><input type="hidden" name="kdate" value="' . $kdate . '" /><input type="hidden" name="sub" value="res" />';
    $str .= '<input type="submit" value="�񤭹���" class="submit" /> ̾��: <input type="text"  value="' . $cname . '" name="name" size="19" class="input" /> E-mail: <input type="text"  value="' . $cmail . '" name="email" size="19" class="input" /><br />';
    $str .= '<textarea name="com" cols="64" rows="7" class="input" wrap="soft"></textarea></form>';
    $str .= '</div>';
    $str .= $this->getThreadFoot( $kdate, $subjectNum );
    return $str;
  }
	
  function getThreadFoot( $kdate, $subjNum )
  {
    global $script, $vars;

    $str  = '<div class="bbs_threadfoot">';
	$str .= '<b>';
	$str .= '<a href="' . $this->getPageName() . '&mode=thread&kdate=' . $kdate . '&ls=all">�����ɤ�</a> ';
	$str .= '<a href="' . $this->getPageName() . '&mode=thread&kdate=' . $kdate . '&subj_num=' . $subjNum . '&ls=50">�ǿ�50</a> ';
	$str .= '<a href="#top">�ĤΥȥå�</a> ';
	$str .= '<a href="' . $this->getPageName() . '">�����</a></b>';
	$str .= '</div>';
    return $str;
  }
	
  /*
   $st : ���쥹�ܤ���
   $to : ���쥹�ܤޤ�
   $ls : �ǿ��β���
  */
  function getThreadContents( $subjectNum, $st, $to, $ls )
  {
    global $script, $vars, $get;
    $subject = $this->subject;
    list($kdate, $title, $res) = explode(SPL, $subject[$subjectNum]);
    $title = ereg_replace("\([0-9]*\)\n$", '', $title);
				
    /*
     ���֥������ȥե����뤫���ɤ߹��फ��DAT��ľ�ܤ�
    */
    if( isset( $get['kdate'] )){
      $log = @file(DAT_DIR . $get[ 'kdate' ]);
      list( ,,,,$title ) = explode(SPL, $log[0]);
      $title = ereg_replace("\([0-9]*\)\n$", '', $title);
    }
    elseif( isset( $subjectNum )){
      $log = @file(DAT_DIR . $kdate);
    }
    $res = count($log);

    /*
     �ǿ��β���ؤ��к�
    */
    if (( isset( $ls ) ) && ( $ls != 'all' )) {
      $st = $res - $ls + 1;
      $to = $res;
    }
    elseif ( $ls == 'all' ) {
      $st = 1;
      $to = $res;
    }
    if ( $st <= 0 ) { $st = 1; }
    if ( $to > $res ) { $to = $res; }
    elseif ( $to == '' ){ $to = $st; }

    $str .= '<div class="bbs_thread_title">��'.($subjectNum+1).':'.$res.'�� <span class="bbs_subject">'.$title.'</span></div>';

    for($t = 1; $t < $to + 1; $t++){
      if (( $t == 1 ) || ( $t >= $st )){
	list($fname,$femail,$fdate,$fcom,$fsub,$fhost,$fid) = explode("<>", $log[$t - 1]);

	//�쥹HTML
	$str .= '<div class="bbs_comment_head">' . $t . ': ̾��: <span class="bbs_name">';
	if( $femail != '' ){
	  $str .= '<a href="mailto:' . $femail . '">' . $fname . '</a></span>:<span class="bbs_date">' . $fdate . '</span>';
	} else {
	  $str .= $fname . '</span>:<span class="bbs_date">' . $fdate . '</span>';	
	}
	if( IDmode ){
	  $str .= '<span class="bbs_id">ID:' . $fid . '</span>';
	}
	$str .= '</div>';
	$str .= '<div class="bbs_comment">' . $fcom . '</div>';
      }
    }
    /* �쥹�ե����� */
    if( $res <= RES_NUM ){
      $str .= $this->getResForm( $kdate, $subjectNum );
    }
    return $str;
  }
  /*
   ɸ��ɽ���Υ��֥������Ȱ���
  */
  function getSubjectList()
  {
    global $script, $vars;
    $num = 0;
    $subject = $this->subject;
    $str .= '<div id="bbs_headline"><a name="subject"></a>';
		
    for( $i = 0; ( $i < HEADLINE_NUM ) && ( $i < count( $subject )) ; $i++ )
      {
	list( $kdate, $title ) = explode( SPL, $subject[$i] );

	$num = $i + 1;
	$str .= '<b><a href="' . $this->getPageName() . '&mode=thread&kdate=' . $kdate . '&subj_num=' . $i . '&ls=50">' . $num . '</a></b> :<a href=#' . ($i+1) . '>' . $title . '</a>��';
      }
    $str .= '������<div align=right><a href="' . $this->getPageName() . '&mode=subjectlist">����åɰ����Ϥ�����</a></div>';
    $str .= '</div></ br>';
    return $str;
  }
  /*
   ���֥������Ȱ���
  */
  function getAllSubjectList( $subjectCount )
  {
    global $script, $vars;
    $num = 0;

    $subject = $this->subject;
    $str .= '<div id="bbs"><div id="bbs_headline"><a name="headline"></a>';
		
    for( $i = 0; $i < count( $subject ) ; $i++ ) {
		list( $kdate, $title ) = explode( SPL, $subject[$i] );

		$num = $i + 1;
		$str .= '<a href="' . $this->getPageName() . '&mode=thread&kdate=' . $kdate . '&subj_num=' . $i . '&ls=50"><b>' . $num . '</b> :' . $title . '</a><br />';
	}
    $str .= '</div></div><br />';
    return $str;
  }
  /*
   ��������åɥե�����
  */
  function getNewThreadForm()
  {
    $str .= '<div id="bbs_newThread_form">';
	$str .= '<form method=post action="' . $this->getPageName() . '">�����ȥ롧<input type="text" name="sub" class=input maxlength="80" size="40" /><input type="submit" value="��������åɺ���" /><br />̾����<input type="text" name="name" size="19" class=input value="" /> E-mail��<input type="text" name="email" size="19" class=input value="" /><br />';
    $str .= '���ơ�<textarea rows=5 cols=60 wrap=OFF name="com" class=input></textarea></form></div>';
    return $str;
  }
  /*
   �饤���󥹾���
  */
  function getLicense()
  {
	  $str  = '<div id="bbs_license">';
	  $str .= '<center>- <a href="' . $this->getPageName() . '&mode=admin">��������</a> -</center><br />';
	  $str .= 'Special Thanks! <a href="http://php.s3.to" target=_blank>��å�PHP!</a><br />';
	  $str .= '<a href="http://wwww.vis.ne.jp/" title="Pukiwiki BBS �Ǽ��� �ץ饰���� plugin">Modified by yuki.ishii</a><br />';
	  $str .= '$Rev: 15 $';
	  $str .= '</div>';
    return $str;
  }
  
  function getResCount( $key )
  {
	  $thread = DAT_DIR . $key;
	  $resArr = @file( $thread );
	  
	  return sizeof( $resArr );
  }

  function getPageName()
  {
	  global $script, $vars;

	  $str = $script . '?cmd=read&page=' . rawurlencode($vars['page']);

	  return $str;
  }
}
?>
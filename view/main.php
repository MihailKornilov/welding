<?php
function _cacheClear($ws_id=WS_ID) {

}//_cacheClear()
function _appScripts() {
	return
		'<link rel="stylesheet" type="text/css" href="'.APP_HTML.'/css/main'.(DEBUG ? '' : '.min').'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.APP_HTML.'/js/main'.(DEBUG ? '' : '.min').'.js?'.VERSION.'"></script>';
}//_appScripts()



function _zayavValToList($arr) {//вставка данных заявок в массив по zayav_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['zayav_id'])) {
			$ids[$r['zayav_id']] = 1;
			$arrIds[$r['zayav_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$zayav = query_arr($sql);

	if(!isset($r['client_phone'])) {
		foreach($zayav as $r)
			foreach($arrIds[$r['id']] as $id)
				$arr[$id] += array('client_id' => $r['client_id']);
		$arr = _clientValToList($arr);
	}

	foreach($zayav as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$dolg = $r['accrual_sum'] - $r['income_sum'];
			$arr[$id] += array(
				'zayav_link' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link">'.
						'№'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_color' => //подсветка заявки на основании статуса
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link color"'._zayavStatus($r['status'], 'bg').'>'.
						'№'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_dolg' => $dolg ? '<span class="zayav-dolg'._tooltip('Долг по заявке', -45).$dolg.'</span>' : ''
			);
		}
	}

	return $arr;
}//_zayavValToList()
function _zayavTooltip($z, $v) {
	return $html =
		'<table>'.
			'<tr><td>'.
				'<td class="inf">'.
					'<div'._zayavStatus($z['status'], 'bg').
						' class="tstat'._tooltip('Статус заявки: '._zayavStatus($z['status']), -7, 'l').
					'</div>'.
					'<b>'.$z['name'].'</b>'.
			'<table>'.
				'<tr><td class="label top">Клиент:'.
					'<td>'.$v['client_name'].
						($v['client_phone'] ? '<br />'.$v['client_phone'] : '').
				'<tr><td class="label">Баланс:'.
					'<td><span class="bl" style=color:#'.($v['client_balans'] < 0 ? 'A00' : '090').'>'.$v['client_balans'].'</span>'.
			'</table>'.
		'</table>';
}
function zayav($v) {
	$data = zayav_spisok($v);
	$v = $data['filter'];

	return
	'<div id="zayav">'.
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left" id="spisok">'.$data['spisok'].
				'<td class="right">'.
					'<div id="buttonCreate">'.
						'<a id="zayav-add">Новая заявка</a>'.
					'</div>'.
					'<div id="find"></div>'.
					'<div class="findHead">Порядок</div>'.
					_radio('sort', array(1=>'По дате добавления',2=>'По обновлению статуса'), $v['sort']).
					_check('desc', 'Обратный порядок', $v['desc']).
					'<div class="condLost'.(!empty($v['find']) ? ' hide' : '').'">'.
						'<div class="findHead">Статус заявки</div>'.
						_rightLink('status', _zayavStatus(), $v['status']).
					'</div>'.
		'</table>'.
	'</div>';
}//zayav()
function zayavFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 20,
		'client_id' => 0,
		'find' => '',
		'sort' => 1,
		'desc' => 0,
		'status' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'client_id' => _num(@$v['client_id']),
		'find' => trim(@$v['find']),
		'sort' => _num(@$v['sort']),
		'desc' => _bool(@$v['desc']),
		'status' => _num(@$v['status']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a class="clear">Очистить фильтр</a>';
			break;
		}
	return $filter;
}//zayavFilter()
function zayav_spisok($v) {
	$filter = zayavFilter($v);
	$filter = _filterJs('ZAYAV', $filter);

	define('ZAYAV_PAGE1', $filter['page'] == 1);


	$page = $filter['page'];
	$limit = $filter['limit'];
	$cond = "`ws_id`=".WS_ID." AND !`deleted`";
	$nomer = 0;

	if($filter['find']) {
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
		$reg = '/('.$filter['find'].')/i';
		if($engRus)
			$regEngRus = '/('.$engRus.')/i';

		if(ZAYAV_PAGE1 && _num($filter['find']))
			$nomer = intval($filter['find']);
	} else {
		if($filter['client_id'])
			$cond .= " AND `client_id`=".$filter['client_id'];
		if($filter['status'])
			$cond .= " AND `status`=".$filter['status'];
	}

	$sql = "SELECT COUNT(*) FROM `zayav` WHERE ".$cond;
	$all = query_value($sql);

	$zayav = array();
	if($nomer) {
		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND `nomer`=".$nomer;
		if($r = query_assoc($sql)) {
			$all++;
			$limit--;
			$r['nomer_find'] = 1;
			$zayav[$r['id']] = $r;
		}
	}

	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Заявок не найдено'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">Заявок не найдено</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, 'а', 'о').' '.$all.' заяв'._end($all, 'ка', 'ки', 'ок').$filter['clear'],
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT
	            *,
	            '' `note`
			FROM `zayav`
			WHERE ".$cond."
			ORDER BY `".($filter['sort'] == 2 ? 'status_dtime' : 'dtime_add')."` ".($filter['desc'] ? 'ASC' : 'DESC')."
			LIMIT "._startLimit($filter);
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if($nomer == $r['nomer'])
			continue;
		$zayav[$r['id']] = $r;
	}

	if(!$filter['client_id'])
		$zayav = _clientValToList($zayav);

	//Заметки
	$sql = "SELECT
				`table_id`,
				`txt`
			FROM `vk_comment`
			WHERE `table_name`='zayav'
			  AND `table_id` IN (".implode(',', array_keys($zayav)).")
			  AND `status`
			ORDER BY `id` ASC";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$zayav[$r['table_id']]['note'] = $r['txt'];

	foreach($zayav as $id => $r) {
		$diff = round($r['accrual_sum'] - $r['income_sum'], 2);
		$send['spisok'] .=
			'<div class="zayav_unit" id="u'.$id.'"'._zayavStatus($r['status'], 'bg').'" val="'.$id.'">'.
				'<h2'.(isset($r['nomer_find']) ? ' class="finded"' : '').'>#'.$r['nomer'].'</h2>'.
				'<a class="name">'.$r['name'].'</a>'.
				'<table class="utab">'.
(!$filter['client_id'] ? '<tr><td class="label">Клиент:<td>'.$r['client_go'] : '').
						 '<tr><td class="label">Дата подачи:'.
							 '<td>'.FullData($r['dtime_add'], 1).
	($r['status'] == 2 ? '<b class="date-ready'._tooltip('Дата выполнения', -47).FullData($r['status_dtime'], 1, 1).'</b>' : '').
							(round($r['accrual_sum'], 2) || round($r['income_sum'], 2) ?
								'<div class="balans'.($diff ? ' diff' : '').'">'.
									'<span class="acc'._tooltip('Начислено', -39).round($r['accrual_sum'], 2).'</span>/'.
									'<span class="opl'._tooltip($diff ? ($diff > 0 ? 'Недо' : 'Пере').'плата '.abs($diff).' руб.' : 'Оплачено', -17, 'l').round($r['income_sum'], 2).'</span>'.
								'</div>'
							: '').
				'</table>'.
				'<div class="note">'.@$r['note'].'</div>'.
			'</div>';
	}

	 $send['spisok'] .= _next($filter + array(
			'type' => 2,
			'all' => $all
		));
	return $send;
}//zayav_spisok()


function zayav_info() {
	if(!$zayav_id = _num(@$_GET['id']))
		return _err('Страницы не существует');

	$sql = "SELECT *
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$zayav_id;
	if(!$z = query_assoc($sql))
		return _err('Заявки не существует.');

	$z['pre_cost'] = _cena($z['pre_cost']);

	$status = _zayavStatus();
	unset($status[0]);
	$history = _history(array('zayav_id'=>$zayav_id));

	return
	'<script type="text/javascript">'.
		'var ZAYAV={'.
				'id:'.$zayav_id.','.
				'nomer:'.$z['nomer'].','.
				'head:"№<b>'.$z['nomer'].'</b>",'.
				'client_id:'.$z['client_id'].','.
				'client_link:"'.addslashes(_clientVal($z['client_id'], 'link')).'",'.
				'status:'.$z['status'].','.
				'status_sel:'._selJson($status).','.
				'name:"'.addslashes($z['name']).'",'.
				'about:"'.addslashes($z['about']).'",'.
				'count:'.$z['count'].','.
				'adres:"'.addslashes($z['adres']).'",'.
				'pre_cost:'.$z['pre_cost'].
			'};'.
	'</script>'.

	'<div id="zayav-info">'.
		'<div id="dopLinks">'.
			'<a class="link a-page sel">Информация</a>'.
			'<a class="link" id="edit">Редактирование</a>'.
			'<a class="link _accrual-add">Начислить</a>'.
			'<a class="link _income-add">Принять платёж</a>'.
			'<a class="link a-page">История</a>'.
		'</div>'.

		'<div class="page">'.
			'<div class="headName">'.
				'Заявка №'.$z['nomer'].
				'<input type="hidden" id="zayav-action" />'.
			'</div>'.
			'<table id="tab">'.
				'<tr><td class="label">Клиент:<td>'._clientVal($z['client_id'], 'go').
				'<tr><td class="label">Название:<td><b>'.$z['name'].'</b>'.
				'<tr><td class="label">Описание:<td>'.$z['about'].
				'<tr><td class="label">Количество:<td><b>'.$z['count'].'</b> шт.'.
 ($z['adres'] ? '<tr><td class="label">Адрес:<td>'.$z['adres'] : '').
 ($z['pre_cost'] ? '<tr><td class="label">Стоимость:<td><b>'.$z['pre_cost'].'</b> руб.' : '').
				'<tr><td class="label">Дата приёма:'.
					'<td class="dtime_add'._tooltip('Заявку '.(_viewerAdded($z['viewer_id_add'])), -70).FullDataTime($z['dtime_add']).
				'<tr><td class="label">Статус:<td>'._zayavStatusButton($z).
			'</table>'.

			_zayavInfoAccrual($zayav_id).
			_zayav_expense($zayav_id).
			_remind_zayav($zayav_id).
			_zayavInfoMoney($zayav_id).
			_vkComment('zayav', $zayav_id).
		'</div>'.

		'<div class="page dn">'.
			'<div class="headName">Заявка №'.$z['nomer'].' - история действий</div>'.
			$history['spisok'].
		'</div>'.

	'</div>';
}//zayav_cartridge_info()

<?php
require_once('config.php');
require_once(API_PATH.'/ajax/vk.php');

switch(@$_POST['op']) {
	case 'zayav_add':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();
		if(!$count = _num($_POST['count']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$about = _txt($_POST['about']);
		$adres = _txt($_POST['adres']);
		$pre_cost = _cena($_POST['pre_cost']);

		$sql = "INSERT INTO `zayav` (
					`ws_id`,
					`client_id`,
					`nomer`,

					`name`,
					`about`,
					`adres`,
					`count`,
					`pre_cost`,

					`status`,
					`status_dtime`,

					`viewer_id_add`,
					`find`
				) VALUES (
					".WS_ID.",
					".$client_id.",
					"._maxSql('zayav', 'nomer', 0, MYSQL_CONNECT).",

					'".addslashes($name)."',
					'".addslashes($about)."',
					'".addslashes($adres)."',
					".$count.",
					".$pre_cost.",

					1,
					current_timestamp,

					".VIEWER_ID.",
					'".addslashes($name)."'
				)";
		query($sql);
		$send['id'] = query_insert_id('zayav');

		_history(array(
			'type_id' => 73,
			'client_id' => $client_id,
			'zayav_id' => $send['id']
		));
		jsonSuccess($send);
		break;
	case 'zayav_edit':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$client_id = _num($_POST['client_id']))
			jsonError();
		if(!$count = _num($_POST['count']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$about = _txt($_POST['about']);
		$adres = _txt($_POST['adres']);
		$pre_cost = _cena($_POST['pre_cost']);

		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$zayav_id;
		if(!$z = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `zayav` SET
					`client_id`=".$client_id.",
					`count`=".$count.",
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`adres`='".addslashes($adres)."',
					`pre_cost`=".$pre_cost.",
					`find`='".addslashes($name)."'
				WHERE `id`=".$zayav_id;
		query($sql);

		if($z['client_id'] != $client_id) {
			$sql = "UPDATE `_money_accrual`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_income`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_refund`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			clientBalansUpdate($z['client_id']);
			clientBalansUpdate($client_id);
		}

		$changes =
			_historyChange('Клиент', $z['client_id'], $client_id, _clientVal($z['client_id'], 'go'), _clientVal($client_id, 'go')).
			_historyChange('Название', $z['name'], $name).
			_historyChange('Описание', $z['about'], $about).
			_historyChange('Количество', $z['count'], $count).
			_historyChange('Адрес', $z['adres'], $adres).
			_historyChange('Стоимость', _cena($z['pre_cost']), $pre_cost);
		if($changes)
			_history(array(
				'type_id' => 72,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'zayav_spisok':
		$_POST['find'] = win1251($_POST['find']);
		$data = zayav_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'zayav_status':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$zayav_status = _num($_POST['status']))
			jsonError();

		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$zayav_id;
		if(!$z = query_assoc($sql))
			jsonError();

		if($z['status'] == $zayav_status)
			jsonError();

		$sql = "UPDATE `zayav`
				SET `status`=".$zayav_status.",
					`status_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql);

		_history(array(
			'type_id' => 71,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => $z['status'],
			'v2' => $zayav_status
		));

		jsonSuccess();
		break;

}

jsonError();
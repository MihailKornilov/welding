<?php
require_once('config.php');
require_once(API_PATH.'/ajax/vk.php');

switch(@$_POST['op']) {
	case 'zayav_add':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();
		if(!$count = _num($_POST['count']))
			jsonError();

		$name = _txt($_POST['name']);
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
}

jsonError();
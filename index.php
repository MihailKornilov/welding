<?php
require_once('config.php');

if(!WS_ACCESS)
	die(_header()._noauth()._footer());

$html = _header();
$html .= _menu();
$html .= _global_index();



switch($_GET['p']) {
	case 'zayav':
		switch(@$_GET['d']) {
			case 'info': $html .= zayav_info(); break;
			default: $html .= zayav(_hashFilter('zayav'));
		}
		break;
}





$html .= _footer();

die($html);

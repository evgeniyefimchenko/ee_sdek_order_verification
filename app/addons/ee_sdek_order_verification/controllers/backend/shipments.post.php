<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }
use Tygh\Registry;

if ($mode == 'manage' || $mode == 'sdek_delivery') {
	fn_ee_sdek_order_verification_start();
}
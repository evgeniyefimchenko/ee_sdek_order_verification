<?php

use Tygh\Registry;
use Tygh\Addons\EeSdekOrderVerification\EeSdekOrderVerification;

if ($mode === 'update' && $action === 'set_hook') {
	$sdek = new EeSdekOrderVerification([0, 1, 2, 3]);
	$sdek->set_my_webhooks();
}
<?php
require_once('../class/qac.php');
$qac = Qac::getInstance();
if ( empty( $_GET['year'] ) || empty( $_GET['slug'] ) ) {
	header('Location: /2017/firstserver');
} else {
	$qac->calendar( $_GET['year'], $_GET['slug'] );
}

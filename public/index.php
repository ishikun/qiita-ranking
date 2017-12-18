<?php
require_once('../class/qac.php');
$qac = Qac::getInstance();
if ( empty( $_GET['slug'] ) ) {
	header('Location: /firstserver');
} else {
	$qac->calendar( $_GET['slug'] );
}

<?php
require_once('../class/qac.php');
$qac = Qac::getInstance();
$qac->cron_exec();

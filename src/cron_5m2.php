<?php
require("Wsql.php");
require("Wsql_ajx.php");
$sql = new Wsql_ajx();

set_time_limit(240);
echo $sql->cron_refresh_geodata();
echo $sql->cron_get_forum_ips();
?>
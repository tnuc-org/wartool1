<?php
require("Wsql.php");
require("Wsql_ajx.php");
$sql = new Wsql_ajx();

echo $sql->cron_cleanup_chars();
echo $sql->cron_optimize_tables();
?>
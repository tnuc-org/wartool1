<?php
require("Wsql.php");
require("Wsql_ajx.php");
$sql = new Wsql_ajx();

set_time_limit(240);
echo $sql->cron_cleanup_tokens();
echo $sql->cron_cleanup_initlv();
echo $sql->cron_refresh_chars(20,'WHERE `e_is_enemy`=1 OR `o_is_ours`=1');
echo $sql->cron_get_onlinelist();
?>
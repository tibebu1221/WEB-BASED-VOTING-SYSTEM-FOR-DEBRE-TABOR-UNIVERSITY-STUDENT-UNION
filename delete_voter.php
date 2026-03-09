<?php
session_start();
include 'connection.php';
if($log != "log"){
	header ("Location: reg_voter.php");
}
$ctrl = $_REQUEST['key'];
$SQL = "DELETE FROM voter WHERE vid = '$ctrl'";
mysql_query($SQL);
mysql_close($db_handle);

print "<script>location.href = 'reg_voter.php'</script>";
?>
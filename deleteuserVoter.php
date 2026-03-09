<?php
session_start();
include 'connection.php';
if($log != "log"){
	header ("Location: manage_voter.php");
}
$ctrl = $_REQUEST['key'];
$SQL = "DELETE FROM voter WHERE u_id = '$ctrl'";
mysql_query($SQL);
mysql_close($db_handle);

print "<script>location.href = 'manage_voter.php'</script>";
?>
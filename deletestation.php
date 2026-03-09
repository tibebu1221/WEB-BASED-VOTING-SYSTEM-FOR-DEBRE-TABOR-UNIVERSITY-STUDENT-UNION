<?php
session_start();
include 'connection.php';
if($log != "log"){
	header ("Location: stations.php");
}
$ctrl = $_REQUEST['key'];
$SQL = "DELETE FROM station WHERE psid = '$ctrl'";
mysql_query($SQL);
mysql_close($db_handle);

print "<script>location.href = 'stations.php'</script>";
?>
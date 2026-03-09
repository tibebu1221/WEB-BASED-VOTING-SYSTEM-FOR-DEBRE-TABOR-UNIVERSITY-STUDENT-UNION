<?php
session_start();
include 'connection.php';
if($log != "log"){
	header ("Location: ov_candidate.php");
}
$ctrl = $_REQUEST['key'];
$SQL = "DELETE FROM candidate WHERE c_id = '$ctrl'";
mysql_query($SQL);
mysql_close($db_handle);

print "<script>location.href = 'ov_candidate.php'</script>";
?>
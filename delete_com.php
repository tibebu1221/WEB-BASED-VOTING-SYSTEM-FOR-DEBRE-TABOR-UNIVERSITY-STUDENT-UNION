<?php
session_start();
include 'connection.php';
if($log != "log"){
	header ("Location: v_comment.php");
}
$ctrl = $_REQUEST['key'];
$SQL = "DELETE FROM comment WHERE c_id = '$ctrl'";
mysql_query($SQL);
mysql_close($db_handle);

print "<script>location.href = 'v_comment.php'</script>";
?>
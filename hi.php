<?php
		include("connection.php");
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<script type="text/javascript">
function change_char(){
	
	var pass = document.getElementById("pw");
	var checkbox = document.getElementById("cb");
	
	if(pass.type == "password"){
		pass.type = "text";
		checkbox.checked = true;
	}else{
		pass.type = "password";
		checkbox.checked = false;
	}
}
	</script>
<!--Header-->
<title> DTUSU Online Voting</title>
<link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
		<!--End of Header-->
</head>
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:12px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px">
<img src="img/log.png" 	width="400px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2F4F4F" id="Menus" style="height:auto;border-radius:1px;">
		<ul>
			<li><a href="index.php">Home</a></li>
			<li ><a href="about.php">About Us</a></li>
			<li ><a href="help.php">Help</a></li>
			<li><a href="contacts.php">Contact Us</a></li>
			<li><a href="h_result.php">Result</a></li>
			<li class="active"><a href="advert.php">advert</a></li>
			<li><a href="dev.php">developer</a></li>
			<li><a href="candidate.php">Candidates</a></li>
			<li><a href="vote.php">Vote</a></li>
			<li><a href="login.php">Login</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center" bgcolor="D3D3D3" style="width:900px;border:1px solid gray;border-radius:1px;" height="200px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
             <img src="deve/HO.png" width="200px" height="300px" border="0">
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
			<table style="width:550px;border:1px solid #51a351; border-radius:1px;">
			<?php
$result = mysql_query("SELECT * FROM election_date");
while($row = mysql_fetch_array($result))
  {

$date=$row['date'];

?>
<tr><td></td>
<td align="left"><b><u><?php echo $date;?></u></b></td></tr>
<?php
  }
print( "</table></center><br><br>");
mysql_close($conn);
?>
			
<tr>
<td colspan="2" bgcolor="#E6E6FA" align="center"  >
<div id="bottom">
<p style="text-align:center;padding-right:20px;">Copyright &copy; 2017 EC.</p>
</div></td>
</tr>
</table>
</body>
</html>
<?php
	include("connection.php");  
 session_start();
if(isset($_SESSION['u_id']))
 {
  $mail=$_SESSION['u_id'];
 } else {
 ?>

<script>
  alert('You are not logged In !! Please Login to access this page');
  alert(window.location='login.php');
 </script>
 <?php
 }
 ?>
<?php

$user_id=$_SESSION['u_id'];
$result=mysql_query("select * from user where u_id='$user_id'")or die(mysql_error);
$row=mysql_fetch_array($result);
$FirstName=$row['fname'];
$middleName=$row['mname'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head> <!--Header-->
<script>
  function isdelete()
  {
   var d = confirm('Are you sure you want to Delete !!');
   if(!d)
   {
    alert(window.location='e_candidate.php');
   }
   else
   {
   return false;
    
   }
  }
  </script>
		
<title>Online Voting</title>
<link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="date/htmlDatepicker.css" rel="stylesheet" />
<script language="JavaScript" src="date/htmlDatepicker.js" type="text/javascript"></script>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
</head><!--End of Header-->
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:12px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<a href="system_admin.php"><img src="img/logo.jpg" width="200px" height="160px" align="left" style="margin-left:10px"></a>
<img src="img/officer.png" 	width="450px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:12px;">
		
		<ul>
			<li><a href="e_officer.php">Home</a></li>
			<li><a href="o_result.php">Result</a></li>
			<li><a href="o_generate.php">Generate Report</a></li>
			<li><a href="regdate.php">start date reg_voter</a></li>
			<li><a href="endregvoter.php">end date reg_voter</a></li>
			<li class="active"><a href="regcan_date.php">register candidate_date</a></li>
			<li><a href="reg_voter.php">Voter</a></li>
			<li><a href="stations.php">Stations</a></li>
			<li><a href="ov_candidate.php">Candidates</a></li>
			<li><a href="o_comment.php">V_Comment</a></li>
			<li><a href="logout.php">Logout</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="400px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
               <img src="deve/o.png" width="200px" height="400px" border="0">
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
           <h1 align="right"></h1>
		   
	<font size="2"><h2> &nbsp;&nbsp; <u>Specify start date registration Here:</u></h2> </font><br/>
	<form name="myform" method="post">
	Date&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="SelectedDate" name="date" onClick="GetDate(date)" placeholder="select date" readonly />
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="go" value="Set"/>
	</form>
	<?php

	if(isset($_POST['go']))
	{
	$start = $_POST['start'];
	mysql_query("DELETE FROM registration WHERE year(start)=year('$start')&& year(end)=year('$end')");
	$qry = mysql_query("INSERT INTO registration values('$start','$end','$user_id')");
	if($qry)
	{
	echo "You specify the date successfully";
	echo '<meta content="15;e_officer.php" http-equiv="refresh"/>';
	}
	else{
	echo "Error occurred while specifying!";
	echo '<meta content="2;dateset.php" http-equiv="refresh"/>';
	
	}
	}
?>
<br><br>
<br><br>
</div>
</div>
</td>
</tr><tr>
<td colspan="2" bgcolor="#E6E6FA" align="center"  >
<div id="bottom">
<p style="text-align:center;padding-right:20px;">Copyright &copy; 2017 EC.</p>
</div></td>
</tr>
</table>
</body>
</html>
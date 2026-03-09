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
$result=mysql_query("select * from voter where vid='$user_id'")or die(mysql_error);
$row=mysql_fetch_array($result);
$FirstName=$row['fname'];
$middleName=$row['mname'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><!--Header-->
<title>Online Voting</title>
<link rel="icon" type="image/jpg" href="img/flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
</head>	<!--End of Header-->
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:12px">
<tr style="height:auto;border-radius:12px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<a href="system_admin.php"><img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px"></a>
<img src="img/voter.png" 	width="400px" style="margin-left:30px;margin-top:0px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:12px;">
		
		<ul>
			<li class="active"><a href="voter.php">Home</a></li>
			<li><a href="cast.php">Cast Vote</a></li>
			<li><a href="voter_comment.php">Comment</a></li>
			<li><a href="voter_result.php">Result</a></li>
			<li><a href="vlogout.php">Logout</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:12px;" height="500px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
            <ul>
                <li><a href="v_change.php">Change Password</a></li>
			    <li><a href="voter_comment.php">Comment</a></li>
				<li><a href="voter_candidate.php">Candidates</a></li>
                <li><a href="voter_result.php">Result</a></li>
				<li><a href="vlogout.php">Logout</a></li>
            </ul>
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
           <h1 align="right"><?php 
echo '<img src="img/people.png" width="40px" height="30px">&nbsp;'.'<font style="text-transform:capitalize;" face="times new roman" color="green" size="3">Hi,&nbsp;'.$FirstName."&nbsp;".$middleName." ".'</font>';?></h1>
<br><br>
<br><br>
<?php
$ctrl = $_REQUEST['key'];
//$ctrl=$_SESSION['c_id'];
$query="SELECT * FROM candidate where c_id='{$ctrl}'";
$result=mysql_query($query);
$count=mysql_num_rows($result);
if(!$result){
die("Candidate is not registered!".mysql_error());
}
if($count==1){
while($row=mysql_fetch_array($result)){
$r1=$row['fname'];
$r2=$row['mname'];
$r3=$row['lname'];
$r4=$row['age'];
$r5=$row['sex'];
$r6=$row['work'];
$r7=$row['education'];
$r8=$row['phone'];
$r9=$row['email'];
$r10=$row['experience'];
$r11=$row['party_symbol'];
$r12=$row['party_name'];
$r13=$row['candidate_photo'];
}
?>
  <form id="form1" method="POST" action="voter_can.php"  onsubmit='return formValidation()'>

 <table valign='top'  align="center" style="border-radius:5px;border:1px solid #336699;width:400px">
 <tr>
 <th colspan="2" bgcolor="#2f4f4f"><font color="white" style="text-transform:uppercase;"><?php echo $r12;?>&nbsp;&nbsp;Party</font><a href="voter_result.php" title="Close"><img src="img/close_icon.gif" align="right"></a></th>
 </tr>
 <tr>
 <td><table>
<tr><td colspan='2'align="center"><img src='<?php echo $r13;?>' width="200px"></td></tr>
<tr><td colspan='2'>&nbsp;</td></tr>
<tr><td><b>Party Name:</b></td><td><?php echo $r12;?></td></tr></table></td>
 <td><table>
<tr><td colspan='2'align="center"><img src='<?php echo $r13;?>' width="200px"></td></tr>
<tr><td colspan='2'><input type="hidden" name="results" value='<?php echo $r12;?>'></td></tr>

<?php
$querys="SELECT * FROM result where choice ='$r12'";
$results=mysql_query($querys);
$counts=mysql_num_rows($results);
echo"<p class='success' style='margin-left:-10px;'>You have&nbsp;<font color='red'>".$counts."</font>&nbsp;vote</p>";
?>
</table>
</td>
</tr>
</table>
 <?php
}
	  

?>
<br><br>
</div>
</div>
</td>
</tr><tr>
<td colspan="2" bgcolor="#E6E6FA" align="center"  >
<div id="bottom">
<p style="text-align:center;padding-right:20px;">Copyright &copy; 2009 EC.</p>
</div></td>
</tr>
</table>
</body>
</html>
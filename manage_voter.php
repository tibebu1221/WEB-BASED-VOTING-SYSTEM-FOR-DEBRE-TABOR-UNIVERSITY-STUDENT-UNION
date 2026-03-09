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
$result=mysql_query("select * from voter where u_id='$user_id'")or die(mysql_error);
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
		<script>
  function isdelete()
  {
   var d = confirm('Are you sure you want to Delete !!');
   if(!d)
   {
    alert(window.location='manage_voter.php');
   }
   else
   {
   return false;
    
   }
  }
  </script>
		
		
</head><!--End of Header-->
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:1px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<a href="system_admin.php"><img src="img/logo.jpg" width="200px" height="160px" align="left" style="margin-left:10px"></a>
<img src="img/system.png" 	width="450px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
		
		<ul>
			<li><a href="system_admin.php">Home</a></li>
			<li><a href="a_candidate.php">Candidates</a></li>
			<li><a href="voters.php">Voters</a></li>
			<li><a href="adminv_result.php">result</a></li>
			<li><a href="logout.php">Logout</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center"bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="400px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
            <ul>
                <li><a href="manage_account.php">Manage Account</a></li>
				<li><a href="manage_voter.php">Manage Voter</a></li>
				<li><a href="a_generate.php">Generate Report</a></li>
				<li><a href="a_candidate.php">Candidates</a></li>
                <li><a href="voters.php">Voters</a></li>
				<li><a href="setDate.php">Set Date</a></li>
				<li><a href="v_comment.php">View Comment</a></li>
				<li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
           <h1 align="right"><?php 
echo '<img src="img/people.png" width="40px" height="30px">&nbsp;'.'<font style="text-transform:capitalize;" face="times new roman" color="green" size="3">Hi,&nbsp;'.$FirstName."&nbsp;".$middleName." ".'</font>';?></h1>
<br><br>
<table align='center' style='width:650px;border-radius:15px;border:1px solid #2f4f4f; -webkit-box-shadow:0 0 18px rgba(0,0,0,0.4); -moz-box-shadow:0 0 18px rgba(0,0,0,0.4); box-shadow:0 0 18px rgba(0,0,0,0.4);'>
<tr>
<th style='height:30px;text-align:center;color:#000;	font-weight:bold;background-color:#51a351;'><font color='white' size='2'>Names</th>
<th style='height:30px;	color:#000;	font-weight:bold;background-color:#2f4f4f;'><font color='white' size='2'>User ID</th>
<th style='height:30px;	color:#000;	font-weight:bold;background-color:#2f4f4f;'><font color='white' size='2'>Status</th>
<th style='height:30px;	color:#000;	font-weight:bold;background-color:#2f4f4f;'><font color='white' size='2'>Delete</th>
<th style='height:30px;	color:#000;	font-weight:bold;background-color:#2f4f4f;'><font color='white' size='2'>Edit</th>
</tr>  
<?php
$result = mysql_query("SELECT * FROM voter");
while($row = mysql_fetch_array($result))
  {
$ctrl = $row['u_id'];
$fname=$row['fname'];
$mname=$row['mname'];
$sex=$row['sex'];
$user_type=$row['role'];
$username=$row['username'];
$password=$row['password'];
$status=$row['status'];
?>
<tr>
<td><?php echo $fname."&nbsp;".$mname;?></td>
<td><?php echo $row['u_id'];?></td>
<td><?php
						if(($status)=='0')
						{
						?>
                       			 <a href="statusvot.php?status=<?php echo $row['u_id'];?>" onclick="return confirm('do you want to activate (<?php echo $fname?>)');">
                        		<img src="IMG/deactivate.png" id="view" width="16" height="16" alt="" />Activated</a>
                        <?php
						}
						if(($status)=='1')
						{
						?>
                       			 <a href="statusvot.php?status=<?php echo $row['u_id'];?>" onclick="return confirm('do you want to De-activate (<?php echo $fname?>)');"> 
                       			 <img src="IMG/activate.png" width="16" id="view" height="16" alt=""  />Deactivated</a>
                        <?php
						}
                        ?>
						</td>	
						<?php
						print("<td style='height:30px;' align = 'center' width = '1'><a href = 'deleteuserVoter.php?key=".$ctrl."'><img width='15px' height='15px' src = 'img/actions-delete.png' title='Delete' onclick='isdelete();'></img></a></td>
		<td style='height:30px;'><a href = 'edituser.php?key=".$ctrl."'><img src = 'img/actions-edit.png' width='15px' height='15px' title='Edit' ></img></a></td>
		");?>
		</tr>
<?php
  }
print( "</table><br><br><br>");
mysql_close($conn);
?>
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
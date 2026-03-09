<?php
	include("connection.php");  
 session_start();
 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><!--Header-->
<title>Online Voting</title>
<link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
</head><!--End of Header-->
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:1px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px">
<img src="img/log.png" 	width="450px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2F4F4F" id="Menus" style="height:auto;border-radius:12px;">
		
		<ul>
			<li><a href="index.php">Home</a></li>
			<li><a href="about.php">About Us</a></li>
			<li><a href="candidate.php">Candidates</a></li>
			<li><a href="vote.php">Vote</a></li>
			<li><a href="contacts.php">Contact Us</a></li>
			<li><a href="login.php">Login</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center" BGCOLOR="D3D3D3" style="width:900px;border:1px solid gray;border-radius:12px;" height="200px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
            <ul>
                <li><a href="index.php">Home</a></li>
		        <li><a href="about.php">About Us</a></li>
				<li><a href="candidate.php">Candidates</a></li>
                <li><a href="vote.php">Vote</a></li>
				<li><a href="contacts.php">Contact Us</a></li>
				<li><a href="help.php">Help</a></li>
				<li><a href="login.php">Login</a></li>
            </ul>
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
           <?php
 if(isset($_POST['sent']))
 {
$date=date("d/m/y ");
$sql="INSERT INTO comment (name,email, content,date,status)
VALUES
('$_POST[fname]','$_POST[email]','$_POST[com]','$date','unread')";

if (!mysql_query($sql,$conn))
  {
  die('Error: ' . mysql_error());
  }
		 echo'  <p class="success">Your Message has been Sent successfuly!</p>';
         echo' <meta content="8;comment.php" http-equiv="refresh" />'; 
		 }
mysql_close($conn)
?><table style="border:1px solid #51a351;width:500px;border-radius:12px;height:200px;text-align:left;box-shadow:1px 2px 20px gray;" align="center" width="500px" >
<form action="comment.php" method="post">
<tr bgcolor="#2F4F4F" ><th colspan="2" ><font color="#ffffff">Feedback form</font><a href="index.php"><img align="right"src="img/close_icon.gif" title="close"></a></th></tr>
  <tr>
	       <td width="200px"> Your Full Name:</td>
		   <td><input type="text" name="fname" id="fname" required x-moz-errormessage="Enter Your Full Name" ></td>
	      </tr>
		 <tr>
	       <td class='para1_text'> Email Address:</td>
		   <td><input type="text" name="email" id="email" required x-moz-errormessage="Enter password"></td>
	     </tr>
  <tr>
	       <td class='para1_text'> Message:</td>
		   <td><textarea rows="6" cols="30" align="center" name="com" id="message" placeholder='Write your comment here' required x-moz-errormessage="Enter Message"></textarea></td>
	     </tr>
  <tr>
    <td>&nbsp;</td>
	<br>
    <td><input type="submit" class="button_example" name="sent" value="Send"/></td>
  </tr>
</table> 
  </form>
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
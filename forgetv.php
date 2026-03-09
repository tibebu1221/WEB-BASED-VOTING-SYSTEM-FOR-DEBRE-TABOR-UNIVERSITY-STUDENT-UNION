<?php   
 session_start();
 include("connection.php");  
 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	
<!--Header-->
<title>Online Voting</title>
<link rel="icon" type="image/jpg" href="img/flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
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
		
		
		
		<!--End of Header-->
</head>
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:1px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="2">
<img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px">
<img src="img/log.png" 	width="450px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
		
		<ul>
			<li ><a href="index.php">Home</a></li>
			<li ><a href="about.php">About Us</a></li>
			<li ><a href="contacts.php">Contact Us</a></li>
			<li><a href="h_result.php">Result</a></li>	
			<li><a href="advert.php">advert</a></li>
			<li><a href="candidate.php">Candidates</a></li>
			<li  class="active"><a href="vote.php">Vote</a></li>
			<li><a href="login.php">Login</a></li>
		</ul>
</td>
</tr>
</table>
<table align="center" style="width:900px;border:1px solid gray;border-radius:1px;" height="500px">
<tr valign="top">
<td><div style="clear: both"></div>

        <div id="left">
            <ul>
                <li>
                    <a href="index.php">Home</a></li>
					                <li>
                    <a href="about.php">About Us</a></li>
					<li>
                    <a href="candidate.php">Candidates</a></li>
                <li>
                    <a href="vote.php">Vote</a></li>
				<li>
                    <a href="contacts.php">Contact Us</a></li>
					<li>
                    <a href="help.php">Help</a></li>
					<li>
                    <a href="comment.php">Comment</a></li>
					<li>
                    <a href="login.php">Login</a></li>
            </ul>
        </div>
		</td>
		<td><div id="right">
            <div class="desk">
           <h1>Forget password page</h1>
<br><br>
           <!--PHP script-->
<?php
 if(isset($_POST['view']))
  {
   $username=$_POST['username'];
   $phone=$_POST['phone'];
   $lname=$_POST['lname'];
   $sql="SELECT * FROM voter where username='$username' AND phone='$phone' AND lname='$lname';"; 
   $result_set=mysql_query($sql,$conn);
   if(!$result_set)
   {
   die("Query failed".mysql_error());
   }
if(mysql_num_rows($result_set)>0)
{
while($row=mysql_fetch_array($result_set))
{
$fname=$row[2];
//$decrypted = $row( $encrypted );
$password=($row['password']);

     

echo"<p class='success'>"."Hi"."&nbsp; &nbsp;".$fname."&nbsp; &nbsp;"."your password is:<font color='red' style='text-decoration:blink'>".$password."</font></p>";
echo'<meta content="12;vote.php" http-equiv="refresh" />';

}}
else
{
echo"<p class='wrong'>Incorrect Input</p>";
echo'<meta content="10;forgetv.php" http-equiv="refresh" />';
}
}
mysql_close($conn);
?>
  
<!--End of PHP-->
<form action="forgetv.php" method="POST">
           <table class="log_table" align="center" >

<tr bgcolor="#2f4f4f" ><th colspan="2" ><font color="#ffffff">Do you forget password?</font></th></tr>
<tr>
<td>
<label>Last Name</label>
</td>
<td>
<input type="text" name="lname" required x-moz-errormessage="Enter last name!"/>
</td>
</tr>
<tr>
<td>
<label>phone number</label>
</td>
<td>
<input type="text" name="phone" required x-moz-errormessage="Enter phone number!"/>
</td>
</tr>
<tr>
<td>
<label>User Name</label>
</td>
<td>
<input type="text" name="username" required x-moz-errormessage="Enter Username!"/>
</td>
</tr>
<tr>
<td>
</td>
<td>
<input type="submit" name="view" value="Recover" class="button_example"/>
<input type="reset" value="Reset" class="button_example"/>
</td>
</tr>
<tr>
<td>
</td>
<td>
<br>
</td>
</tr>
</form>
</table>
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
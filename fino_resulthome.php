<?php
include("connection.php");  
session_start(); // Good practice to include, though not used here
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!--Header-->
<title>Online Voting</title>
<link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
<link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
<link href="menu.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<!--End of Header-->
<body>
<table align="center" bgcolor="#D3D3D3" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
<tr style="height:auto;border-radius:12px;background: white url(img/tbg.png) repeat-x left top;">
<th colspan="1">
<img src="img/logo.JPG" width="200px" height="160px" align="left" style="margin-left:10px">
<img src="img/log.png"  width="450px" style="margin-left:30px;margin-top:40px" align="center">
</th>
</tr>
<tr>
<td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li ><a href="about.php">About Us</a></li>
            <li ><a href="help.php">Help</a></li>
            <li><a href="contacts.php">Contact Us</a></li>
            <li class="active"><a href="h_result.php">Result</a></li>
            <li><a href="advert.php">advert</a></li>
            <li><a href="candidate.php">Candidates</a></li>
            <li><a href="vote.php">Vote</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
</td>
</tr>
</table>
<table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="500px">
<tr valign="top">
<td width="220">
    <div id="left">
        <img src="deve/dt.PNG" width="200px" height="300px" border="0"><br>
        <table style="width:100%;border:1px solid #ccc;background-color: #ffffff; margin-top:10px;">
        <?php
        // **FIXED**: Using mysqli_query
        $result = mysqli_query($conn, "SELECT * FROM election_date");
        while($row = mysqli_fetch_array($result))
        {
            $date = $row['date'];
        ?>
        <!-- **FIXED**: Corrected HTML and added security -->
        <td style='color:red; text-align:center;'><b><p>Election Date:</p></b><b><?php echo htmlspecialchars($date);?></b></td>
        <?php
        }
        ?>
        </table>
    </div>
</td>
<td>
    <div id="right">
        <div class="desk">
        <?php
        // Check if the 'key' is set in the URL
        if(isset($_REQUEST['key'])) {
            $ctrl = $_REQUEST['key'];

            // ==== SECURE QUERY USING PREPARED STATEMENTS ====
            
            // 1. Prepare the statement with a placeholder (?)
            $query = "SELECT fname, mname, lname, candidate_photo FROM candidate WHERE c_id = ?";
            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) {
                // 2. Bind the user-provided ID to the placeholder (assuming c_id is a string 's')
                mysqli_stmt_bind_param($stmt, "s", $ctrl);

                // 3. Execute the statement
                mysqli_stmt_execute($stmt);

                // 4. Get the result
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result);
                    // Using htmlspecialchars to prevent XSS attacks
                    $r1 = htmlspecialchars($row['fname']);
                    $r2 = htmlspecialchars($row['mname']);
                    $r3 = htmlspecialchars($row['lname']);
                    $r13 = htmlspecialchars($row['candidate_photo']);
        ?>
            <table valign='top' align="center" style="border-radius:5px;border:1px solid #336699;width:400px">
                <tr>
                    <th colspan="2" bgcolor="#2f4f4f">
                        <font color="white" style="text-transform:uppercase;">Candidate Result</font>
                        <a href="h_result.php" title="Close"><img src="img/close_icon.gif" align="right"></a>
                    </th>
                </tr>
                <tr>
                    <td>
                        <table width="100%">
                            <tr><td colspan='2' align="center"><img src='<?php echo file_exists($r13) ? $r13 : 'img/default_candidate.jpg'; ?>' width="200px" alt="Candidate Photo"></td></tr>
                            <tr><td><b>Candidate:</b></td><td><b><?php echo $r1 . ' ' . $r2 . ' ' . $r3; ?></b></td></tr>
                        <?php
                        // **FIXED**: Second query for vote count (also secured)
                        $querys = "SELECT COUNT(*) as vote_count FROM result WHERE choice = ?";
                        $stmt2 = mysqli_prepare($conn, $querys);
                        mysqli_stmt_bind_param($stmt2, "s", $ctrl); // Use c_id instead of party_name
                        mysqli_stmt_execute($stmt2);
                        $results = mysqli_stmt_get_result($stmt2);
                        $counts = mysqli_fetch_array($results)['vote_count'];
                        echo "<tr><td colspan='2'><p class='success' style='margin: 10px 0;'>Total Votes: <font color='red'>" . $counts . "</font></p></td></tr>";
                        mysqli_stmt_close($stmt2);
                        ?>
                        </table>
                    </td>
                </tr>
            </table>
        <?php
                } else {
                    echo "<p class='wrong'>Candidate not found!</p>";
                }
                mysqli_stmt_close($stmt);
            } else {
                die("Database query failed: " . mysqli_error($conn));
            }
        } else {
            echo "<p class='wrong'>No candidate selected. Please go back to the results page and choose a candidate.</p>";
        }
        ?>
        <br><br>
        </div>
    </div>
</td>
</tr>
<tr>
<td colspan="2" bgcolor="#E6E6FA" align="center">
    <div id="bottom">
        <p style="text-align:center;padding-right:20px;">Copyright © 2025 EC.</p>
    </div>
</td>
</tr>
</table>
<?php
// **FIXED**: Close the connection at the end
mysqli_close($conn);
?>
</body>
</html>
<?php
 $username = "s2801913";
 $password = "s2801913";
 $database = "d2801913";
 $link = mysqli_connect("127.0.0.1", $username, $password, $database);
 $output=array();
 /* Select queries return a resultset */
 if ($result = mysqli_query($link, "SELECT * from CARS")) {
 while ($row=$result->fetch_assoc()){
 $output[]=$row;
 }
 }
 mysqli_close($link);
 echo json_encode($output);
 ?>

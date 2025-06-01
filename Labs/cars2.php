<?php
$username = "s2801913";
$password = "s2801913";
$database = "d2801913";
$link = mysqli_connect("127.0.0.1", $username, $password, $database);

$brand = $_REQUEST["brand"];
$output=array();
/* Select queries return a resultset */
if ($r = mysqli_query($link, "SELECT * from cars where
    brand=’$brand’")) {
    while ($row=$r->fetch_assoc()){
        $output[]=$row;
    }
} 

mysqli_close($link);
echo json_encode($output);
?>

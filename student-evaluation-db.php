<?php 
    try {
        $conn = mysqli_connect("localhost","root","","student_evaluation_db");
    } catch (mysqli_sql_exception $e) {
        echo"". $e ->getMessage() ."";
    }
    
?>


<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

/**
 * Created by PhpStorm.
 * User: mahmoud
 * Date: 8/8/17
 * Time: 10:10 PM
 */
include 'DataSource.php';
$connection = new \Fritill\GoogleCharts\DataSource();
$data = $connection->widgets();
header('Content-Type: application/json');
echo json_encode($data);
?>
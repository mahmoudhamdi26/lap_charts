<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

/**
 * Created by PhpStorm.
 * User: mahmoud
 * Date: 8/8/17
 * Time: 10:10 PM
 */

include 'ChartsDrawer.php';
include 'DataSource.php';
$connection = new \Fritill\GoogleCharts\DataSource();
$drawer = new \Fritill\GoogleCharts\ChartsDrawer();
?>


<html>
<head>

</head>
<body>
<?php //print_r($connection->getData($_GET['id'])); ?>
<?php $connection->getData($_GET['id']); ?>
<!--Div that will hold the pie chart-->
<div id="chart_div" style="width:600px; height:600px;"></div>
<div id="chart_div_unit"></div>
<div id="chart_div_info"></div>


<!--Load the AJAX API-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    var data;
    var chart;
    google.charts.load('current', {'packages':['corechart', 'annotationchart', 'gauge', 'table']});
    <?php $drawer->draw('drawChart', 'chart_div', 'chart', 'data', $connection->getData($_GET['id'])); ?>
</script>
</body>
</html>
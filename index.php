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
?>


<html>
<head>

</head>
<body>
<div style="display: block;">
    <?php $ds = new \Fritill\GoogleCharts\DataSource(); $ds->getData(7); ?>
    <table>
        <?php foreach($connection->widgets() as $widget): ?>
            <tr>
                <td><?php echo $widget['id'] ?></td>
                <td><a href="show.php?id=<?php echo $widget['id'] ?>"><?php echo $widget['name'] ?></a></td>
                <td><?php echo $widget['freq'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
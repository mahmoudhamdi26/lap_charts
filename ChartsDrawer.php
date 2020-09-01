<?php
/**
 * Created by PhpStorm.
 * User: mahmoud
 * Date: 8/8/17
 * Time: 10:01 PM
 */

namespace Fritill\GoogleCharts;


class ChartsDrawer {

    var $dataVarName, $targetDivID, $chartVarName, $functionName;

    public function getLineDataTables(){
        $inputs = [
            ['Mushrooms', 3],
            ['Onions', 1],
            ['Olives', 1],
            ['Zucchini', 1],
            ['Pepperoni', 2]
        ];

        foreach($inputs as $k){
            echo "['".$k[0]."', ".$k[1]."],";
        }
    }

    public function drawMultiple($functionName, $target_div_id, $chartVarName, $dataVarName,  $data){

        $this->dataVarName = $dataVarName;
        $this->targetDivID = $target_div_id;
        $this->chartVarName = $chartVarName;
        $this->functionName = $functionName;

//        print_r($data);
        // Set a callback to run when the Google Visualization API is loaded.
        echo "google.charts.setOnLoadCallback($functionName);";
        echo 'function '.$functionName.'() {'.PHP_EOL;

        for($i=0; $i<count($data['columns']);$i++){
            $this->initDataTableMultiple($data, $i);
            $this->fillingDataTableMultiple($data, $i);
            $this->options($data);
            $this->drawChartMultiple($chartVarName[$i], $dataVarName[$i], $target_div_id[$i], $data);
        }

        echo '}'.PHP_EOL;

//        echo "document.getElementById('chart_div_unit').innerText = '".$data['info']['unit']."';";
//        echo "document.getElementById('chart_div_info').innerText = '".json_encode($data['info']['info'])."';";
    }

    public function draw($functionName, $target_div_id, $chartVarName, $dataVarName,  $data){
        if(is_array($target_div_id)){
            $this->drawMultiple($functionName, $target_div_id, $chartVarName, $dataVarName, $data);
            return;
        }

//        if($data['info']['chart'] == 'pie' && count($data['columns'])>2){
//            $this->drawMultiple($functionName, $target_div_id, $chartVarName, $dataVarName, $data);
//            return;
//        }
        //echo "document.getElementById('".$target_div_id."').id = '$target_div_id".$data['info']['chart'].$data['info']['id']."';";

        $this->dataVarName = $dataVarName;
        $this->targetDivID = $target_div_id;
        $this->chartVarName = $chartVarName;
        $this->functionName = $functionName;

//        print_r($data);
        // Set a callback to run when the Google Visualization API is loaded.
        echo "google.charts.setOnLoadCallback($functionName);";
        echo 'function '.$functionName.'() {'.PHP_EOL;

        $this->initDataTable($data);
        $this->fillingDataTable($data);
        $this->options($data);
        $this->drawChart($chartVarName, $target_div_id, $data);

        echo '}'.PHP_EOL;

        //echo "document.getElementById('".$target_div_id."_unit').innerText = '".$data['info']['unit']."';";
        //echo "document.getElementById('".$target_div_id."_info').innerText = '".json_encode($data['info']['info'])."';";
    }

    public function initDataTable($data){
        //        data = new google.visualization.DataTable();
//        data.addColumn('string', 'Values');
//        data.addColumn('number', 'Frequency');
        if($data['info']['chart']=='pie'){
            echo "$this->dataVarName = new google.visualization.DataTable();";
            echo "$this->dataVarName.addColumn('string', 'Frequency');";
            echo "$this->dataVarName.addColumn('number', 'value');";
        }else{
            echo "$this->dataVarName = new google.visualization.DataTable();";
            echo "$this->dataVarName.addColumn('string', 'Frequency');";
            for($i=0; $i<count($data['columns']); $i++){
                echo "$this->dataVarName.addColumn('number', '".$data['columns'][$i]."');";
            }
        }
//        foreach($data['dataTable'] as $v){
//            $dataCount = count($v);
//            for($i=1; $i<$dataCount; $i++){
//                echo "$this->dataVarName.addColumn('number', '');";
//            }
//            break;
//        }
    }
    public function initDataTableMultiple($data, $i){
        //        data = new google.visualization.DataTable();
//        data.addColumn('string', 'Values');
//        data.addColumn('number', 'Frequency');
        echo $this->dataVarName[$i]." = new google.visualization.DataTable();";
        echo $this->dataVarName[$i].".addColumn('string', 'Frequency');";
        echo $this->dataVarName[$i].".addColumn('number', '".$data['columns'][$i]."');";
    }

    public function fillingDataTable($data){
//        print_r($data['dataTable']);die;
        if($data['info']['chart']=='pie'){
            echo "$this->dataVarName.addRows([";
            foreach($data['dataTable'] as $k){
                for($i=1; $i<count($k); $i++){
                    echo '[';
//                    echo "'$k[0]'";
                    echo "'".$data['columns'][$i-1]."'";
                    echo ",$k[$i]";
                    echo '],';
                }
//            echo "['".$k[0]."', ".$k[1].", $k[2]]";
            }
            echo ']);';
        }else{
            echo "$this->dataVarName.addRows([";

            foreach($data['dataTable'] as $k){
                echo '[';
                for($i=0; $i<count($k); $i++){
                    if($i==0){
                        echo "'$k[$i]'";
                    }else{
                        echo ",$k[$i]";
                    }
                }
                echo '],';
//            echo "['".$k[0]."', ".$k[1].", $k[2]]";
            }

            echo ']);';
        }
    }
    public function fillingDataTableMultiple($data, $pos){
        echo $this->dataVarName[$pos].".addRows([";

        foreach($data['dataTable'] as $k){
            echo '[';
            for($i=0; $i<count($k); $i++){
                if($i==0){
                    echo "'$k[$i]'";
                }else if($i-1 == $pos){
                    echo ",$k[$i]";
                }
            }
            echo '],';
//            echo "['".$k[0]."', ".$k[1].", $k[2]]";
        }

        echo ']);';
    }

    function options_($data){
        if($data['info']['chart']=='combo'){
            $aggregateNum =  count($data['columns'])-1;
            echo "var options = {'title':'".$data['info']['name']."',
            'width':305,
            'height':150,
            seriesType: 'bars',
            series: {".$aggregateNum.": {type: 'line'}}
            };";
        }
        elseif($data['info']['chart']=='accumulated_area'){
            //backgroundColor: '#ddd',
            //colors: ['#4374E0', '#53A8FB', '#F1CA3A', '#E49307'],
            // legend: { position: 'bottom' },
            echo "var options = {'title':'".$data['info']['name']."',
            vAxis: {title: 'Accumulated Values'},
            isStacked: true,
            connectSteps: false,
            'width':400,
            'height':300};";
        }
        else{
            echo "var options = {'title':'".$data['info']['name']."',
            'width':400,
            'height':300};";
        }
    }
    function options($data){
        echo "var options = {".PHP_EOL;

        echo "'title':''"; //.$data['info']['name']."'";
//        echo ",'width':100%";
//        echo ",'height':100%";
        if(isset($_GET['w'])){
            echo ",'width':".$_GET['w'];
        }
        if(isset($_GET['h'])){
            echo ",'height':".$_GET['h'];
        }

        if($data['info']['subtitle'] != null && !empty($data['info']['subtitle'])){
            echo ",subtitle: '".$data['info']['subtitle']."'";
        }

        /// Legend
        echo ",legend: { position: '".$data['info']['legend']."', alignment: '".$data['info']['legend_alignment']."', maxLines: 3, textStyle: {color: 'black', fontSize: 12} }";


        if($data['info']['chart']=='donut'){
            echo ",pieHole: 0.4";
        }

        if($data['info']['chart']=='combo'){
            $aggregateNum =  count($data['columns'])-1;
            echo ",seriesType: 'bars'";
            echo ",series: {".$aggregateNum.": {type: 'line'}}";
        }elseif($data['info']['chart']=='accumulated_area'){
            echo ",vAxis: {title: 'Accumulated Values'}";
            echo ",isStacked: true";
            echo ",connectSteps: false";
        }
        elseif($data['info']['chart']=='column' || $data['info']['chart']=='bar'
            || $data['info']['chart']=='area'){
            if($data['info']['is_stacked'] && $data['info']['stacked_percent']){
                echo ",isStacked: 'percent'";
            }else if($data['info']['is_stacked']){
                echo ",isStacked: true";
            }else{
                echo ",isStacked: false";
            }

            if($data['info']['direction'] == 'v'){
                echo ",bars: 'vertical'";
            }else{
                echo ",bars: 'horizontal'";
            }
        }


        echo "};".PHP_EOL;
    }

    function drawChart($chartVarName, $target_div_id,  $data){
        if($data['info']['chart']=='bar'){
            echo $chartVarName." = new google.visualization.BarChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        if($data['info']['chart']=='line'){
            echo $chartVarName." = new google.visualization.LineChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        else if($data['info']['chart']=='area'){
            echo $chartVarName." = new google.visualization.AreaChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='column'){
            echo $chartVarName." = new google.visualization.ColumnChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='gauge'){
            echo $chartVarName." = new google.visualization.Gauge(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='pie'){
            echo $chartVarName." = new google.visualization.PieChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='donut'){
            echo $chartVarName." = new google.visualization.PieChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='combo'){
            echo $chartVarName." = new google.visualization.ComboChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='scatter'){
            echo $chartVarName." = new google.visualization.ScatterChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='accumulated_area'){
            echo $chartVarName." = new google.visualization.SteppedAreaChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='table'){
            echo $chartVarName." = new google.visualization.Table(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }
        elseif($data['info']['chart']=='bubble'){
            echo $chartVarName." = new google.visualization.BubbleChart(document.getElementById('$target_div_id'));";
            echo "$this->chartVarName.draw($this->dataVarName, options);";
        }

    }
    function drawChartMultiple($chartVarName,$dataVarName, $target_div_id,  $data){
        if($data['info']['chart']=='bar'){
            echo $chartVarName." = new google.visualization.BarChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        if($data['info']['chart']=='line'){
            echo $chartVarName." = new google.visualization.LineChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        else if($data['info']['chart']=='area'){
            echo $chartVarName." = new google.visualization.AreaChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='column'){
            echo $chartVarName." = new google.visualization.ColumnChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='gauge'){
            echo $chartVarName." = new google.visualization.Gauge(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='pie'){
            echo $chartVarName." = new google.visualization.PieChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='combo'){
            echo $chartVarName." = new google.visualization.ComboChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='scatter'){
            echo $chartVarName." = new google.visualization.ScatterChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='accumulated_area'){
            echo $chartVarName." = new google.visualization.SteppedAreaChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='table'){
            echo $chartVarName." = new google.visualization.Table(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }
        elseif($data['info']['chart']=='bubble'){
            echo $chartVarName." = new google.visualization.BubbleChart(document.getElementById('$target_div_id'));";
            echo "$chartVarName.draw($dataVarName, options);";
        }

    }
}
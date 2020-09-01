<?php
/**
 * Created by PhpStorm.
 * User: mahmoud
 * Date: 8/8/17
 * Time: 10:21 PM
 */

namespace Fritill\GoogleCharts;


class DataSourceOLD2 {

    private $conn = null;

    function getConnectionOld(){
        if($this->conn!= null){
            return $this->conn;
        }

        $this->conn = pg_connect("host=localhost dbname=lap user=postgres password=a");

        return $this->conn;
    }

    function getConnection(){
        if($this->conn == null){
//            $this->conn = new \PDO('pgsql:host=localhost;dbname=lap', 'postgres', 'a');
            $this->conn = new \PDO('pgsql:host=localhost;dbname=lap', 'postgres', 'aA111111');
        }

        return $this->conn;
    }

    function initConnection(){
        if($this->conn == null){
//            $this->conn = new \PDO('pgsql:host=localhost;dbname=lap', 'postgres', 'a');
            $this->conn = new \PDO('pgsql:host=localhost;dbname=lap', 'postgres', 'aA111111');
        }
    }

    function countries(){
        $this->initConnection();
        //$result = pg_query($this->getConnection(), "SELECT lastname FROM users");
        $stmt = $this->conn->query("SELECT * FROM countries");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//            print_r($row);
            echo sprintf('<option value="%s">%s</option>', $row['id'], $row['name']);
        }
    }

    function widgets(){
        $this->initConnection();

        $toReturn = [];

        $widgetStmt = $this->conn->query("SELECT id, name, freq, chart from widgets");
        while ($row = $widgetStmt->fetch(\PDO::FETCH_ASSOC)) {
            array_push($toReturn, $row);
        }

        return $toReturn;
    }

    function freqs(){
        $this->initConnection();
        //$result = pg_query($this->getConnection(), "SELECT lastname FROM users");
        $stmt = $this->conn->query("SELECT DISTINCT frequency FROM indicators");
        $users = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            echo sprintf('<option value="%s">%s</option>', $row['frequency'], $row['frequency']);
        }
    }

    function getData($id){
        $this->initConnection();
        $toReturn = [];

        $widgetStmt = $this->conn->query("SELECT * from widgets where id=$id");
        $widget = null;
        while ($row = $widgetStmt->fetch(\PDO::FETCH_ASSOC)) {
            $widget =$row;
        }
        if(empty($widget)){
            /// Invalid ID
        }

        $toReturn['info'] = $widget;
        if($widget['source'] == 'INDICATOR'){
            $toReturn['columns'] = $this->getColumns($id);
            $toReturn['dataTable'] = $this->getDataTableRows($id);
        }else{
            $toReturn['columns'] = $this->getColumnsManual($widget);
            $toReturn['dataTable'] = $this->getDataTableRowsManual($widget);
        }


        return $toReturn;
    }

    function getColumns($id){
        $this->initConnection();

        $columns = [];

        $sectorsStmt = $this->conn->query("SELECT * from widget_sectors where widgets_id=$id");
        while ($rowSector = $sectorsStmt->fetch(\PDO::FETCH_ASSOC)) {
            $indicatorID = $rowSector['indicators_id'];
            $indicatorStmt = $this->conn->query("SELECT * FROM indicators where id=$indicatorID");
            while ($row = $indicatorStmt->fetch(\PDO::FETCH_ASSOC)) {
                array_push($columns, $row['name']);
            }
        }

        return $columns;
    }
    function getColumnsManual($widget){
        $columns = [];

        $dataArr = json_decode($widget['manual_data'], true);
        foreach($dataArr as $k=>$v){
            array_push($columns, $v['title']);
        }

        return $columns;
    }

    function getDataTableRows_($id){
        $this->initConnection();
        $dataTable = [];

        $sectorsStmt = $this->conn->query("SELECT * from widget_sectors where widgets_id=$id");
        while ($rowSector = $sectorsStmt->fetch(\PDO::FETCH_ASSOC)) {
            $indicatorID = $rowSector['indicators_id'];
            $freqsArr = explode(":", $rowSector['selected_freq']);

            $fieldsIdsStmt = $this->conn->query("SELECT fields_id FROM indicator_fields where indicators_id=$indicatorID");
            $fieldsIDs = "0";
            while ($rowIF = $fieldsIdsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fieldsIDs.=",".$rowIF['fields_id'];
            }

            $fieldsWithOptions = [];
//            return "SELECT * FROM options where fields_id in ($fieldsIDs) and val=0";
            $optionsStmt = $this->conn->query("SELECT * FROM options where fields_id in ($fieldsIDs) and val='0'");
            while ($rowOption = $optionsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fieldsWithOptions[$rowOption['fields_id']] = $rowOption['id'];
            }

            $where = "published=true and approved=true and indicators_id=$indicatorID";
            foreach($fieldsWithOptions as $k=>$v){
                $where.=" and field_$k=$v";
            }
            $dataSql = "select val, year, month, day, freq, frequency_order from indicator_data where $where order by frequency_order";
//            print_r($dataSql);
            $dataStmt = $this->conn->query($dataSql);
            while ($rowData = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                if(in_array($rowData['freq'].'-'.$rowData['frequency_order'], $freqsArr)){
                    if(array_key_exists($rowData['freq'].'-'.$rowData['frequency_order'], $dataTable)){
                        array_push($dataTable[$rowData['freq'].'-'.$rowData['frequency_order']], $rowData['val']);
                    }else{
                        $dataTable[$rowData['freq'].'-'.$rowData['frequency_order']]=[
                            $rowData['freq'].'-'.$rowData['frequency_order'], // LABEL
                            $rowData['val']
                        ];
                    }
                }
            }
        }

//        print_r($dataTable);
        return $dataTable;
    }

    function getDataTableRows($id){
        $this->initConnection();
        $dataTable = [];

        $indicatorsFreqs = [];

        $sectorsStmt = $this->conn->query("SELECT * from widget_sectors where widgets_id=$id");
        $sectorRows = $sectorsStmt->fetchAll(); //(\PDO::FETCH_ASSOC);
        foreach ($sectorRows as $rowSector) {
            $freqsArr = explode(":", $rowSector['selected_freq']);
            array_push($indicatorsFreqs, [ $rowSector['indicators_id']=>$freqsArr ]);
        }
        $numOfColumns = count($indicatorsFreqs);

        $titles = [];
        $indicatorsData = [];
        foreach ($sectorRows as $rowSector) {
            $indicatorID = $rowSector['indicators_id'];
            $freqsArr = explode(":", $rowSector['selected_freq']);

            $fieldsIdsStmt = $this->conn->query("SELECT fields_id FROM indicator_fields where indicators_id=$indicatorID");
            $fieldsIDs = "0";
            while ($rowIF = $fieldsIdsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fieldsIDs.=",".$rowIF['fields_id'];
            }

            $fieldsWithOptions = [];
//            return "SELECT * FROM options where fields_id in ($fieldsIDs) and val=0";
            $optionsStmt = $this->conn->query("SELECT * FROM options where fields_id in ($fieldsIDs) and val='0'");
            while ($rowOption = $optionsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fieldsWithOptions[$rowOption['fields_id']] = $rowOption['id'];
            }

            $where = "published=true and approved=true and indicators_id=$indicatorID";
            foreach($fieldsWithOptions as $k=>$v){
                $where.=" and field_$k=$v";
            }
            $dataSql = "select val, year, month, day, freq, frequency_order from indicator_data where $where order by frequency_order";
//            print_r($dataSql);
            $dataStmt = $this->conn->query($dataSql);
            while ($rowData = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                if(in_array($rowData['freq'].'-'.$rowData['frequency_order'], $freqsArr)){
                    if(array_key_exists($indicatorID, $indicatorsData)){
                        /// Prepare the Title
                        if($rowData['freq'] == 'Year'){
                            $freqVisibleTitle = $rowData['year'];
                        }else{
                            $freqVisibleTitle = $rowData['freq'].' '.$rowData['frequency_order'].', '.$rowData['year'];
                        }

                        if(!empty($rowData['month'])){
                            $freqVisibleTitle.="-".$rowData['month'];
                        }
                        array_push($indicatorsData[$indicatorID],
                            [$freqVisibleTitle, $rowData['val']]);
                    }else{
                        if($rowData['freq'] == 'Year'){
                            $freqVisibleTitle = $rowData['year'];
                        }else{
                            $freqVisibleTitle = $rowData['freq'].' '.$rowData['frequency_order'].', '.$rowData['year'];
                        }

                        if(!empty($rowData['month'])){
                            $freqVisibleTitle.="-".$rowData['month'];
                        }
                        $indicatorsData[$indicatorID] = [ [$freqVisibleTitle, $rowData['val']] ];
                    }
                }
            }
        }

//        print_r($indicatorsFreqs);
//        print_r('<br/>');
        $counter = 1;
        foreach($indicatorsData as $IDK=>$IDV){
//            print_r($IDK);
//            print_r($IDV);
//            print_r('<br/>');

            foreach($IDV as $IDVRow){
                if(array_key_exists($IDVRow[0], $dataTable)){
//                    $dataTable[$IDVRow[0]][$counter] = $IDVRow[1];
                    array_push($dataTable[$IDVRow[0]],$IDVRow[1]);
                }else{
                    $holdersArray = [$IDVRow[0]];
                    for($ic=1; $ic<$counter; $ic++){
                        array_push($holdersArray, 0);
                    }
                    array_push($holdersArray, $IDVRow[1]);
                    $dataTable[$IDVRow[0]] = $holdersArray;
                }
            }

            $counter++;
        }

        /// Making sure everything is ok in case of only one indicator has Data
        foreach($dataTable as $k=>$DTitem){
            $countDTItem = count($DTitem);
            // +1 for adding Frequency Title column
            for($countDTItem; $countDTItem<$numOfColumns+1; $countDTItem++){
                array_push($dataTable[$k], 0);
            }
        }

//        print_r($dataTable);
        return $dataTable;
    }
    function getDataTableRowsManual($widget){
        $dataTable = [];
        $freqsArr = explode(',',$widget['name']);
        foreach($freqsArr as $freq){
//            $dataTable[$freq] = [$freq];
            array_push($dataTable, [$freq]);
        }

        $dataArr = json_decode($widget['manual_data'], true);
        foreach($dataArr as $v){
            foreach($v['data'] as $dKIndex=>$dV){
                array_push($dataTable[$dKIndex], $dV);
            }
        }

//        print_r($dataTable);
        return $dataTable;
    }

}
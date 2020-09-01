<?php
/**
 * Created by PhpStorm.
 * User: mahmoud
 * Date: 8/8/17
 * Time: 10:21 PM
 */

namespace Fritill\GoogleCharts;


class DataSource {

    private $dsn = "pgsql:host=localhost;dbname=lap";
    private $username = "postgres";
//    private $pass = "aA111111";
	private $pass = "a";
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
            $this->conn = new \PDO($this->dsn, $this->username, $this->pass);
        }

        return $this->conn;
    }

    function initConnection(){
        if($this->conn == null){
//            $this->conn = new \PDO('pgsql:host=localhost;dbname=lap', 'postgres', 'a');
            $this->conn = new \PDO($this->dsn, $this->username, $this->pass);
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

//        var_dump($toReturn);
//        $r = json_encode($toReturn, JSON_PRETTY_PRINT);
//        echo $r;
//        die;
        return $toReturn;
    }

    function getColumns($id){
        $this->initConnection();

        $columns = [];

        $sectorsStmt = $this->conn->query("SELECT * from widget_sectors where widgets_id=$id");
        while ($rowSector = $sectorsStmt->fetch(\PDO::FETCH_ASSOC)) {
            if($rowSector['selected_fields'] == 'ALL'){
                $indicatorID = $rowSector['indicators_id'];
                $indicatorStmt = $this->conn->query("SELECT * FROM indicators where id=$indicatorID");
                while ($row = $indicatorStmt->fetch(\PDO::FETCH_ASSOC)) {
                    array_push($columns, $row['name']);
                }
            }else{
                $indicatorName = '';
                $optionName = '';
                $fieldName = '';

                $indicatorID = $rowSector['indicators_id'];
                $indicatorStmt = $this->conn->query("SELECT * FROM indicators where id=$indicatorID");
                while ($rowIndicator = $indicatorStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $indicatorName = $rowIndicator['name'];
                }

                $optionID = $rowSector['selected_fields'];
                $optionStmnt = $this->conn->query("SELECT * FROM options where id=$optionID");
                while ($rowOption = $optionStmnt->fetch(\PDO::FETCH_ASSOC)) {
                    $optionName = $rowOption['label'];
                    /// Get Field
                    $fieldID = $rowOption['fields_id'];
                    $fieldStmnt = $this->conn->query("SELECT * FROM fields where id=$fieldID");
                    while ($rowField = $fieldStmnt->fetch(\PDO::FETCH_ASSOC)) {
                        $fieldName = $rowField['name'];
                    }
                }

                array_push($columns, $indicatorName.'-'.$fieldName.'-'.$optionName);
            }
        }

        return $columns;
    }
    function getColumnsManual($widget){
        $columns = [];

        $dataArr = json_decode($widget['manual_data'], true);
        if($dataArr != null && !empty($dataArr)){
            foreach($dataArr as $k=>$v){
                array_push($columns, $v['title']);
            }
        }

        return $columns;
    }

    function getDataTableRows_($id){
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
    function getDataTableRows($id){
        $this->initConnection();
        $dataTable = [];

        $indicatorsFreqs = [];

        $widgetStmt = $this->conn->query("SELECT * from widgets where id=$id");
        $widgetRow = $widgetStmt->fetch(\PDO::FETCH_ASSOC);
        foreach (explode(',', $widgetRow['selected_freqs']) as $freqItem) {
            array_push($indicatorsFreqs, $freqItem);
        }

        $sectorsStmt = $this->conn->query("SELECT * from widget_sectors where widgets_id=$id");
        $sectorRows = $sectorsStmt->fetchAll(); //(\PDO::FETCH_ASSOC);
        $numOfColumns = count($sectorRows);
//	    print_r('<pre>'); print_r($sectorRows); print_r('</pre>'); die;
        $titles = [];
        $indicatorsData = [];
        $sectorCounter = 0;
        foreach ($sectorRows as $rowSector) {
//        	echo '<pre>';
//        	print_r($rowSector);
//        	echo '</pre>';
            $indicatorID = $rowSector['indicators_id'];
            $selectedOptionID = $rowSector['selected_fields'];
            $selectedFieldID = null;
            if($selectedOptionID != 'ALL'){
                $selectedOptionStmt = $this->conn->query("SELECT * FROM options where id=$selectedOptionID");
                $rowSelectedOption = $selectedOptionStmt->fetch(\PDO::FETCH_ASSOC);
                $selectedFieldID = $rowSelectedOption['fields_id'];
            }

            $fieldsIdsStmt = $this->conn->query("SELECT fields_id FROM indicator_fields where indicators_id=$indicatorID");
            $fieldsIDs = "0";
            while ($rowIF = $fieldsIdsStmt->fetch(\PDO::FETCH_ASSOC)) {
                if($selectedOptionID == 'ALL'){
                    $fieldsIDs.=",".$rowIF['fields_id'];
                }/// Don't add the field with option selected as we will add it later with the selected option
                /// after getting the totals options ids for the other fields
                elseif($rowIF['fields_id'] != $selectedFieldID){
                    $fieldsIDs.=",".$rowIF['fields_id'];
                }
            }

            $fieldsWithOptions = [];
//            return "SELECT * FROM options where fields_id in ($fieldsIDs) and val=0";
            $optionsStmt = $this->conn->query("SELECT * FROM options where fields_id in ($fieldsIDs) and val='0'");
            while ($rowOption = $optionsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fieldsWithOptions[$rowOption['fields_id']] = $rowOption['id'];
            }

            $where = "published=true and approved=true and indicators_id=$indicatorID";
            /// Add the selected option for the selected field if not ALL selected
            if($selectedOptionID != 'ALL'){
                $where.=" and field_$selectedFieldID=$selectedOptionID";
            }
            /// Totals fields
            foreach($fieldsWithOptions as $k=>$v){
                $where.=" and field_$k=$v";
            }

            /// Adding freqs to query
            if($widgetRow['freq'] == 'ANNUALLY'){
                $freqWhere = "";
                foreach($indicatorsFreqs as $fi){
                    if($freqWhere==""){
                        $freqWhere.="('".$fi."'";
                    }else{
                        $freqWhere.=",'".$fi."'";
                    }
                }
                $freqWhere.=")";
                $where.=" and year in $freqWhere order by year";
            }elseif($widgetRow['freq'] == 'MONTHLY'){
                $freqWhere = "";
                foreach($indicatorsFreqs as $fi){
                    $freqParts = explode(":", $fi);
                    if($freqWhere==""){
                        $freqWhere.="((".$freqParts[0].",".$freqParts[1].")";
                    }else{
                        $freqWhere.=",(".$freqParts[0].",".$freqParts[1].")";
                    }
                }
                $freqWhere.=")";
                $where.=" and (month, year) in $freqWhere order by year, month";
            }elseif($widgetRow['freq'] == 'WEEKLY'){
                $freqWhere = "";
                foreach($indicatorsFreqs as $fi){
                    $freqParts = explode(":", $fi);
                    if($freqWhere==""){
                        $freqWhere.="(day in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3,4,5,6,7)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(8,9,10,11,12,13,14)";
                        }elseif($freqParts[0] == 3){
                            $freqWhere.="(15,16,17,18,19,20,21)";
                        }elseif($freqParts[0] == 4){
                            $freqWhere.="(22,23,24,25,26,27,28,29,30,31)";
                        }

                        $freqWhere.=" and month=".$freqParts[1]." and year=".$freqParts[2];
                        $freqWhere.=")";
                    }else{
                        $freqWhere.=" or (day in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3,4,5,6,7,8)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(9,10,11,12,13,14,15,16)";
                        }elseif($freqParts[0] == 3){
                            $freqWhere.="(17,18,19,20,21,22,23,24)";
                        }elseif($freqParts[0] == 4){
                            $freqWhere.="(25,26,27,28,29,30,31)";
                        }

                        $freqWhere.=" and month=".$freqParts[1]." and year=".$freqParts[2];
                        $freqWhere.=")";
                    }
                }
                $where.=" and ($freqWhere) order by year, month, day";
            }
            elseif($widgetRow['freq'] == 'QUARTERLY'){
                $freqWhere = "";
                foreach($indicatorsFreqs as $fi){
                    $freqParts = explode(":", $fi);
                    if($freqWhere==""){
                        $freqWhere.="(month in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(4,5,6)";
                        }elseif($freqParts[0] == 3){
                            $freqWhere.="(7,8,9)";
                        }elseif($freqParts[0] == 4){
                            $freqWhere.="(10,11,12)";
                        }

                        $freqWhere.="  and year=".$freqParts[1];
                        $freqWhere.=")";
                    }else{
                        $freqWhere.=" or (month in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(4,5,6)";
                        }elseif($freqParts[0] == 3){
                            $freqWhere.="(7,8,9)";
                        }elseif($freqParts[0] == 4){
                            $freqWhere.="(10,11,12)";
                        }

                        $freqWhere.=" and year=".$freqParts[1];
                        $freqWhere.=")";
                    }
                }
                $where.=" and ($freqWhere) order by year, month";
            }
            elseif($widgetRow['freq'] == 'SEMI-ANNUALLY'){
                $freqWhere = "";
                foreach($indicatorsFreqs as $fi){
                    $freqParts = explode(":", $fi);
                    if($freqWhere==""){
                        $freqWhere.="(month in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3,4,5,6)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(7,8,9,10,11,12)";
                        }

                        $freqWhere.="  and year=".$freqParts[1];
                        $freqWhere.=")";
                    }else{
                        $freqWhere.=" or (month in ";
                        if($freqParts[0] == 1){
                            $freqWhere.="(1,2,3,4,5,6)";
                        }elseif($freqParts[0] == 2){
                            $freqWhere.="(7,8,9,10,11,12)";
                        }

                        $freqWhere.=" and year=".$freqParts[1];
                        $freqWhere.=")";
                    }
                }
                $where.=" and ($freqWhere) order by year, month";
            }


            $dataSql = "select val, year, month, day, freq, frequency_order from indicator_data where $where"; //order by frequency_order
//	        print_r($dataSql); die;
	        $dataStmt = $this->conn->query($dataSql);
            while ($rowData = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                if(array_key_exists($indicatorID, $indicatorsData)){
                    /// Prepare the Title
                    if($rowData['freq'] == 'Year'){
                        $freqVisibleTitle = $rowData['year'];
                    }else{
                        //$freqVisibleTitle = $rowData['freq'].' '.$rowData['frequency_order'].', '.$rowData['year'];
                        $freqVisibleTitle = $rowData['year'];
                    }

//                    if(!empty($rowData['month'])){
//                        $freqVisibleTitle.="-".$rowData['month'];
//                    }


                    if(!empty($rowData['month']) &&
                        ($widgetRow['freq'] == 'MONTHLY' || $widgetRow['freq'] == 'WEEKLY' || $widgetRow['freq'] == 'DAILY')){
                        $freqVisibleTitle.="-".$rowData['month'];
                    }elseif(!empty($rowData['month']) && $widgetRow['freq'] == 'QUARTERLY'){
                        if($rowData['month'] <= 3){
                            $freqVisibleTitle.="- First Quarter";
                        }elseif($rowData['month'] <= 6){
                            $freqVisibleTitle.="- Second Quarter";
                        }
                        elseif($rowData['month'] <= 9){
                            $freqVisibleTitle.="- Third Quarter";
                        }
                        else{
                            $freqVisibleTitle.="- Last Quarter";
                        }
                    }elseif(!empty($rowData['month']) && $widgetRow['freq'] == 'SEMI-ANNUALLY'){
                        if($rowData['month'] <= 6){
                            $freqVisibleTitle.="- First Half";
                        }elseif($rowData['month'] <= 12){
                            $freqVisibleTitle.="- Second Half";
                        }
                    }

                    if(!empty($rowData['day'])){
                        if($rowData['day'] <= 7){
                            $freqVisibleTitle.="- Week 1";
                        }elseif($rowData['day'] <= 14){
                            $freqVisibleTitle.="- Week 2";
                        }
                        elseif($rowData['day'] <= 21){
                            $freqVisibleTitle.="- Week 3";
                        }
                        else{
                            $freqVisibleTitle.="- Week 4";
                        }
//                        $freqVisibleTitle.="-".$rowData['day'];
                    }
                    array_push($indicatorsData[$indicatorID],
                        [$freqVisibleTitle, $sectorCounter, $rowData['val']]);
                }else{
                    if($rowData['freq'] == 'Year'){
                        $freqVisibleTitle = $rowData['year'];
                    }else{
//                        $freqVisibleTitle = $rowData['freq'].' '.$rowData['frequency_order'].', '.$rowData['year'];
                        $freqVisibleTitle = $rowData['year'];
                    }

                    if(!empty($rowData['month']) &&
                        ($widgetRow['freq'] == 'MONTHLY' || $widgetRow['freq'] == 'WEEKLY' || $widgetRow['freq'] == 'DAILY')){
                        $freqVisibleTitle.="-".$rowData['month'];
                    }elseif(!empty($rowData['month']) && $widgetRow['freq'] == 'QUARTERLY'){
                        if($rowData['month'] <= 3){
                            $freqVisibleTitle.="- First Quarter";
                        }elseif($rowData['month'] <= 6){
                            $freqVisibleTitle.="- Second Quarter";
                        }
                        elseif($rowData['month'] <= 9){
                            $freqVisibleTitle.="- Third Quarter";
                        }
                        else{
                            $freqVisibleTitle.="- Last Quarter";
                        }
                    }elseif(!empty($rowData['month']) && $widgetRow['freq'] == 'SEMI-ANNUALLY'){
                        if($rowData['month'] <= 6){
                            $freqVisibleTitle.="- First Half";
                        }elseif($rowData['month'] <= 12){
                            $freqVisibleTitle.="- Second Half";
                        }
                    }

                    if(!empty($rowData['day'])){
                        if($rowData['day'] <= 7){
                            $freqVisibleTitle.="- Week 1";
                        }elseif($rowData['day'] <= 14){
                            $freqVisibleTitle.="- Week 2";
                        }
                        elseif($rowData['day'] <= 21){
                            $freqVisibleTitle.="- Week 3";
                        }
                        else{
                            $freqVisibleTitle.="- Week 4";
                        }
//                        $freqVisibleTitle.="-".$rowData['day'];
                    }
                    $indicatorsData[$indicatorID] = [ [$freqVisibleTitle,$sectorCounter, $rowData['val']] ];
                }
            }

            $sectorCounter++;
        }

//        print_r($indicatorsFreqs);
//        echo '<pre>'; print_r($indicatorsData); echo '</pre>'; die;
//        print_r('<br/>');
        $counter = 1;
        foreach($indicatorsData as $IDK=>$IDV){
	        // get the start and the end to complete the missing sectors counter
	        // To solve the problem of, if 2 fields options selected in the same indicator and the first one is empty
	        $IDV_NewArray = $IDV;
//	        $IDV_NewArray = [];
//	        $maxIDVRowSectorCounter = 0;
//	        foreach($IDV as $IDVRow){
//		        if($IDVRow[1] > $maxIDVRowSectorCounter){
//		        	$maxIDVRowSectorCounter = $IDVRow[1];
//		        }
//	        }
//
//	        for($i=0; $i<=$maxIDVRowSectorCounter; $i++){
//		        $found = false;
//		        foreach($IDV as $IDVRow){
//		        	if($IDVRow[1] == $i){
//			        	$found = true;
//			        	$IDV_NewArray[$i]= $IDVRow;
//			        	break;
//			        }
//		        }
//
//		        if(!$found){
//			        $IDV_NewArray[$i] = [
//			        	$IDV[0][0], $i, 0
//			        ];
//		        }
//	        }

//	        echo '<pre>'; print_r($IDV_NewArray); echo('</pre>'); die;

            foreach($IDV_NewArray as $IDVRow){
                if(array_key_exists($IDVRow[0], $dataTable)){
//                    $dataTable[$IDVRow[0]][$counter] = $IDVRow[1];
//                    array_push($dataTable[$IDVRow[0]],$IDVRow[2]);
	                $dataTable[$IDVRow[0]][$IDVRow[1]+1] = $IDVRow[2];
                }else{
                    $holdersArray = [$IDVRow[0]];
                    for($ic=1; $ic<$counter; $ic++){
                        array_push($holdersArray, 0);
                    }
//                    array_push($holdersArray, $IDVRow[2]);
	                $holdersArray[$IDVRow[1]+1] = $IDVRow[2];
                    $dataTable[$IDVRow[0]] = $holdersArray;
                }
            }

            $counter++;
        }

//        echo '<pre>'; print_r($dataTable); echo('</pre>'); die;

	    ///////////////////////////////
        /// Adding missing years
//	    $dataTableNewOrdered = [];
//	    if(count($dataTable) > 1){
//		    $existedYearsArr = array_keys($dataTable);
//		    $firstYear = $existedYearsArr[0];
//		    $lastYear = $existedYearsArr[count($existedYearsArr) - 1];
//		    // Making sure last year > first year
//		    if($lastYear < $firstYear){
//		    	$tmpYear = $lastYear;
//		    	$lastYear = $firstYear;
//		    	$firstYear = $tmpYear;
//		    }
//
//		    /// Adding missing years
//		    for($i=$firstYear; $i<=$lastYear; $i++){
//			    if(!key_exists($i, $dataTable)){
////				    array_push($dataTableNewOrdered, [$i]);
//				    $dataTableNewOrdered[$i] = [$i];
//			    }else{
//				    $dataTableNewOrdered[$i] = $dataTable[$i];
//			    }
//		    }
//
//		    $dataTable = $dataTableNewOrdered;
//	    }

	    /// END Adding missing years
	    /// /////////////////////////////

//        print_r($dataTableNewOrdered); die;
//	    echo '<pre>'; print_r($dataTable); echo('</pre>'); die;

	    /// Fixing Missing values
//	    print_r(array_keys($dataTable[2008])); die;

        /// Making sure everything is ok in case of only one indicator has Data
        foreach($dataTable as $k=>$DTitem){
	        $postions = array_keys($DTitem);
//	        echo '<pre>'; print_r(end($postions)); echo('</pre>'); die;
	        for($i=0; $i<=end($postions); $i++){
	        	if(!in_array($i, $postions)){
	        		$DTitem[$i] = 0;
		        }
	        }

	        $dataTable[$k] = $DTitem;
//	        echo '<pre>'; print_r($DTitem); echo('</pre>'); die;

            $countDTItem = count($DTitem);
            // +1 for adding Frequency Title column
            for($countDTItem; $countDTItem<$numOfColumns+1; $countDTItem++){
                array_push($dataTable[$k], 0);
            }
        }

//        print_r($dataTable);
//        echo '<pre>'; print_r($dataTable); echo '</pre>'; die;
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
        if($dataArr != null && !empty($dataArr)){
            foreach($dataArr as $v){
                foreach($v['data'] as $dKIndex=>$dV){
                    array_push($dataTable[$dKIndex], $dV);
                }
            }
        }

//        print_r($dataTable); die;
        return $dataTable;
    }

}

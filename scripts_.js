/**
 * Created by mahmoud on 8/10/17.
 */

var freq = null;
var countryid = null;
var dataTable = null;

function countrySelected(){
    countryid = document.getElementById('selectCountry').value;
    getIndicators();
}
function freqSelected(){
    freq = document.getElementById('selectFreq').value;
    getIndicators();
}

function getIndicators(){
    if(freq == null || countryid==null){
        return;
    }

    var url = 'http://localhost/lap/public/api/charts/indicators/'+countryid+'/'+freq;

    $.ajax({
        url: url,
        success: function (result) {
            $('#selectIndicators').html($(result));
        },
        error: function (jqXHR, textStatus, errorThrown) {
            /// Show error message
            alert('Something went wrong, Please try again.');
        }
    });
}

function indicatorSelected(){
    var url = 'http://localhost/lap/public/api/charts/indicators/data/';
    var vals = $('#selectIndicators').val();
    var ids = "0";
    for(var i=0; i<vals.length; i++){
        ids = ids + "," + vals[i];
    }
    url = url + ids;
    console.log(url);

    $.ajax({
        url: url,
        success: function (result) {
            dataTable =  new google.visualization.DataTable();
            dataTable.addColumn('string', 'Topping');
            dataTable.addColumn('number', 'Slices');
            dataTable.addColumn('number', 'SlicesX');
            dataTable.addRows([
                ['Mushrooms', 3, 1],
                ['Onions', 1, 2],
                ['Olives', 1, 10],
                ['Zucchini', 1, 0],
                ['Pepperoni', 2, 70]
            ]);

            // Set chart options
            var options = {'title':'How Much Pizza I Ate Last Night',
                'width':400,
                'height':300};


            var chart = new google.charts.Line(document.getElementById('chart_div'));
            chart.draw(dataTable, options);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            /// Show error message
            //alert('Something went wrong, Please try again.');
        }
    });

    console.log(vals);
}

function drawStuff() {
    if(dataTable == null){
        return;
    }

    //var data = new google.visualization.arrayToDataTable([
    //    ['Galaxy', 'Distance', 'Brightness'],
    //    ['Canis Major Dwarf', 8000, 23.3],
    //    ['Sagittarius Dwarf', 24000, 4.5],
    //    ['Ursa Major II Dwarf', 30000, 14.3],
    //    ['Lg. Magellanic Cloud', 50000, 0.9],
    //    ['Bootes I', 60000, 13.1]
    //]);

    var options = {
        width: 800,
        chart: {
            title: 'Nearby galaxies',
            subtitle: 'distance on the left, brightness on the right'
        },
        bars: 'horizontal', // Required for Material Bar Charts.
        series: {
            0: { axis: 'distance' }, // Bind series 0 to an axis named 'distance'.
            1: { axis: 'brightness' } // Bind series 1 to an axis named 'brightness'.
        },
        axes: {
            x: {
                distance: {label: 'parsecs'}, // Bottom x-axis.
                brightness: {side: 'top', label: 'apparent magnitude'} // Top x-axis.
            }
        }
    };



    var chart = new google.charts.Bar(document.getElementById('dual_x_div'));
    chart.draw(dataTable, options);
}


//Base Layer with Open Street Maps
// var tracking = require('../../helpers/trackFunctions');
const http = new XMLHttpRequest();
const url = 'localhost:3000/api/track';
var baseMapLayer = new ol.layer.Tile({
  source: new ol.source.OSM()
});
//Construct the Map Object
var map = new ol.Map({
  target: 'map',
  layers: [ baseMapLayer],
  view: new ol.View({
          center: ol.proj.fromLonLat([112.689839, -7.276889]),
          zoom: 17 //Initial Zoom Level
        })
});
//Set up an  Style for the marker note the image used for marker

//Adding a marker on the map
var marker = new ol.Feature({
  geometry: new ol.geom.Point(
    ol.proj.fromLonLat([112.688602, -7.276343])
  )
});
// marker.setStyle(iconStyle);
var vectorSource = new ol.source.Vector({
  features: [marker]
});

var markerVectorLayer = new ol.layer.Vector({
  source: vectorSource,
});
// add style to Vector layer style map
map.addLayer(markerVectorLayer);



function updateCoordinate(item) { 
  // Structure of the input Item
  // {"Coordinate":{"Longitude":80.2244,"Latitude":12.97784}}    
  var featureToUpdate = marker;
  var coord = ol.proj.fromLonLat([item.Coordinate.Longitude, item.Coordinate.Latitude]);
  featureToUpdate.getGeometry().setCoordinates(coord);
}


var longlats =
[[-7.276343, 112.688602],
[-7.276902, 112.688999],
[-7.277647, 112.688092],
[-7.277684, 112.688859],
[-7.275561, 112.688907]];
var count = 1;
var item = {};
item.id = marker.getId;
item.Coordinate = {};

setInterval(function() {
  item.Coordinate.Longitude = longlats[count][1];
  item.Coordinate.Latitude = longlats[count][0];
  count++;
  
  
  updateCoordinate(item);
}, 5000);

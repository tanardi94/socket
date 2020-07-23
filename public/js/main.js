
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
  var data = {
    lat: longlats[count][0],
    lng: longlats[count][1]
  };
  var x = new FormData();
  x.append('lat', longlats[count][0]);
  x.append('lng', longlats[count][1]);
  
  $.ajax({
    url: url,
    data: {
      lat: item.Coordinate.Latitude,
      lng: item.Coordinate.Longitude
    },
    type: 'POST',
    beforeSend: function (xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijc1OWRmYzhjNmIyYTAxNzI2ODAwMzFiYTkzMWE5NjgxYTdmZjQ4Mzg0Mzk0NWY4MjhlY2QxODc4OGU5YzBhMzczODM0ZGU1YTQ4ZmFmNDA2In0.eyJhdWQiOiIxIiwianRpIjoiNzU5ZGZjOGM2YjJhMDE3MjY4MDAzMWJhOTMxYTk2ODFhN2ZmNDgzODQzOTQ1ZjgyOGVjZDE4Nzg4ZTljMGEzNzM4MzRkZTVhNDhmYWY0MDYiLCJpYXQiOjE1OTM1ODcyOTYsIm5iZiI6MTU5MzU4NzI5NiwiZXhwIjoxNjI1MTIzMjk2LCJzdWIiOiIzNDAzMjYiLCJzY29wZXMiOltdfQ.Qy8kDSv295KlTHVTTyuJv97yAzBYrgMQillS4omOHfUeXzNuMipys3EV1luiGYpA5VXwzacj_eo_fg5KtPYTwdmP4lQu7i3aEyc_TVcipVymIKT7TaIEAOYw7_fTlcj_gJ1yEbvrjRaDv-Wg3YDodpbDXZ_gjGjLuSnBpqEKoXPpoTmArvwt5XGLQHJ9t5rVT3SXJwVZ6GDtYGc2oUiLpCS9hWbMPj73Sl9Z4zQ62a8SHOIXaMmbyyDFuESzfqZkwJWLjb-xLRZAHfHYl2MSdSfRcpqSyOEoUUnPgDH2jUsiaFqKTCpXw5Ea0ugI4_4JiKOsvFF2ER_xyhwkci8oD862MLIFA5Z4v4iC6uOIsOoDETpl-Siz073afSgnY9G8eNxBmtgbN7vtp8PKkL9q5BnUKnEzMPG9VL5c8lOU_UOUv2mNFzVgILgW4i3qE0akLf7oO2nU5e7NdBbR4tpE-ftR5MZOIy1avbKdLigLEuoL-t0LOO7Im_Ss2uilWxglqQ_IhUwnM8WzwNySGhmWWykvrP01KuKihMxaw7kIUuwJ7bUWrbnTbROnjGpxZ_qOQp0R2ElYRKAhLYg2hAwvfDPvt85ZfRPXIxx7FRKOTEVx8tj-PFVCeWEtvBaOwJEuXd1jsGE7UcK_bRhmF2vANxXk5RR1l3HSVvtWiQVN1mE');
    },
    success: function() {
      console.log('success' + item.Coordinate);
    }
  });
  updateCoordinate(item);
}, 5000);

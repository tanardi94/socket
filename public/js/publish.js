
const http = new XMLHttpRequest();
const url = 'localhost:3000/api/track';

var longlats = [
  [-7.276343, 112.688602],
  [-7.276902, 112.688999],
  [-7.277647, 112.688092],
  [-7.277684, 112.688859],
  [-7.275561, 112.688907]
];

const socket = io({ transports: ['websocket'] });
var count = 0;
setInterval(function() {
  console.log(count);
  if (count < longlats.length){
    var item = {};
    item.Coordinate = {};
    item.Coordinate.Count = count;
    item.Coordinate.Longitude = longlats[count][1];
    item.Coordinate.Latitude = longlats[count][0];

    // var data = "lat=" + longlats[count][0] + "&lng=" + longlats[count][1];

    var data = {
      lat: longlats[count][0],
      lng: longlats[count][1]
    };

    console.log(data);
    socket.emit('lastKnownLocations', item);
    count++;
  }
}, 5000);
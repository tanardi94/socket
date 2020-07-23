
var longlats = [
  [-7.276343, 112.688602],
  [-7.276902, 112.688999],
  [-7.277647, 112.688092],
  [-7.277684, 112.688859],
  [-7.275561, 112.688907]
];

const socket = io({ transports: ['websocket'] });
var count = 1;
setInterval(function() {
  console.log(count);
  if (count < 10000){
    var item = {};
    item.Coordinate = {};
    item.Coordinate.Longitude = longlats[count][1];
    item.Coordinate.Latitude = longlats[count][0];
    count++;
    socket.emit('lastKnownLocation', item);
  }
}, 1000);
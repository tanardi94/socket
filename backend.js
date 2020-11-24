const Express = require("express")();
const Http = require("http").Server(Express);
const Socketio = require("socket.io")(Http);
const cors = require('cors')
const redis = require('redis')

const client = redis.createClient();

Http.listen(5000, () => {
    console.log("Listening at :5000...");
});
// const redis = require('redis')
// const client = redis.createClient()


const markers = [];

Socketio.on("connection", socket => {
    // for(let i = 0; i < markers.length; i++) {
    //     socket.emit("marker", markers[i]);

    // }
    socket.on("marker", data => {
        markers.push(data);
        console.log(data);
        client.set('marker', data.lat + ', ' + data.lng);
        Socketio.emit("marker", data);
    });
});

// const platform = new H.service.Platform({
//     //   'app_id': '0JK53jN7Faa5a5rF35Sz',
//     //   'app_code': 'SqWcupOF8jY3JHYYKD0NSw'
//     'apikey': 'BCBkA-zdB3UwXP9SnW_PXII0Kv-uxevI4usfR1iZIK4'
//   });
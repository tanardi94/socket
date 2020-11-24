var app     =     require("express")();
var mysql   =     require("mysql");
var http    =     require('http').Server(app);
var io      =     require("socket.io")(http);
const bodyParser = require('body-parser');




/* Creating POOL MySQL connection.*/

var pool    =    mysql.createPool({
      connectionLimit   :   100,
      host              :   'localhost',
      user              :   'root',
      password          :   '',
      database          :   'fb_status',
      debug             :   false
});


const apiRoutes = require('./api-routes');
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({
  extended: true
}));

app.get("/",function(req,res){
    res.sendFile(__dirname + '/index.html');
});

app.get("/chating", (req, res) => {
  res.sendFile(__dirname + '/chat.html')
})

app.get("/rooms", (req, res) => {
  res.sendFile(__dirname + "/views/index.html");
})

app.use('/api', apiRoutes);

http.listen(3000,function(){
  console.log("Listening on 3000");
});


// users = [];
// io.on('connection', function(socket) {
//    console.log(' Yess A user is connected');
//    socket.on('setUsername', function(data) {
//       if(users.indexOf(data) > -1) {
//         socket.emit('userExists', data + ' username is taken! Try some other username.');
//       } else {
//         users.push(data);
//         socket.emit('userSet', {username: data});
//       }
//    })

//    socket.on('msg', function (data) {
//      io.sockets.emit('newmsg', data)
//    })

// });


/*  This is auto initiated event when Client connects to Your Machien.  */

// io.on('connection',function(socket){  
//     console.log("A user is connected");
//     socket.on('status added',function(status){
//       add_status(status,function(res){
//         if(res){
//             io.emit('refresh feed',status);
//         } else {
//             io.emit('error');
//         }
//       });
//     });
// });

var users = []
io.on('connection', function(socket) {
    console.log(`Connection : Socket_id = ${socket.id}`);

    socket.on('subscribe', data => {
        const userName = data.userName;
        const roomName = data.roomName;

        users.push(data)
        socket.join(roomName);
        console.log(`Username: ${userName} joins ${roomName} room`);
        io.to(`${roomName}`).emit('newUserToChatRoom', data);
    })

    socket.on('unsubscribe', data => {
        console.log('unsubscribe triggered')
        const roomData = JSON.parse(data)
        const userName = roomData.userName
        const roomName = roomData.roomName

        console.log(`Username: ${userName} has left room: ${roomName}`);
        socket.broadcast.to(`${roomName}`).emit('userLeft', userName)
        socket.leave(`${roomName}`)
    })

    socket.on('newData', data => {
        console.log('newData triggered')
        const roomName = data.roomName

        io.to(`${roomName}`).emit('updateData', data)
    })

    socket.on('disconnect', function () {
        console.log("One of sockets disconnected from our server.")
    });
});
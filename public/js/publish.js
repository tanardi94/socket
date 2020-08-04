
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
var count = 1;
setInterval(function() {
  console.log(count);
  if (count < longlats.length){
    var item = {};
    item.Coordinate = {};
    item.Coordinate.Longitude = longlats[count][1];
    item.Coordinate.Latitude = longlats[count][0];

    // var data = "lat=" + longlats[count][0] + "&lng=" + longlats[count][1];

    var data = {
      lat: longlats[count][0],
      lng: longlats[count][1]
    };

    console.log(data);
    

    http.onreadystatechange = () => {
      if(http.readyState == 4 && http.status == 200) {
        console.log("Success " + item.Coordinate.Longitude);
      } else {
        console.log("Error", http.responseText);
      }
    };
    http.open("POST", url, true);
    http.withCredentials = "true";
    http.setRequestHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijc2YjUxZjU3OWJhZTRlYjQzMTJkOWJmYmRhYTYzZTJjZjdmOTNmM2RmYWE2ZTU1ZjkzYTUzYWQyMTg5NWJhZTkzMzZlN2FjNmMzMGQ5NWU3In0.eyJhdWQiOiIzIiwianRpIjoiNzZiNTFmNTc5YmFlNGViNDMxMmQ5YmZiZGFhNjNlMmNmN2Y5M2YzZGZhYTZlNTVmOTNhNTNhZDIxODk1YmFlOTMzNmU3YWM2YzMwZDk1ZTciLCJpYXQiOjE1OTY0MzY3ODEsIm5iZiI6MTU5NjQzNjc4MSwiZXhwIjoxNjI3OTcyNzgxLCJzdWIiOiIzNDAzMjYiLCJzY29wZXMiOltdfQ.ICw_sDlMIcveC3zDC-VQedFQ34XN_pGXbtx70I_1GooyvFzmo4cWPF3p0Jn9HX_uTcJ2iQgpgiFGp9dvw4r13diKlgOkXKPqeveXjn8nRY-564aNNrQAtx2Bxkx7OhK75_ddQ3UmshKAzi4paJJiYchRf1wR7JhgVoDTtvRQDCBAG0PLoXoEHnlI77s457ZTMtQ9zVgJTy_nOw55W5HDEnIloMwx4H1IXjL_cVOPkBixFLMMrRyVugsCavG1N4CfYimt4R8Tu0TrV67knIgtANDz8HXBTEIjs2RAg9ZmYNTE_An5xBp-h3Gl7UOMk6MUdGE9GIxI3DUShQoceqaF2COMisnJvAb6XojjQKte7WyGb9VgJW3gjXQ_yWpym4hvorydrZSzHQY6ou5kYWB6yBB4IoKo21WWT1LmGSYTbcebHBfEpmhoayd1J4k3o9_9LGnkQBComQvRUJN6fWw7T8Z9VJ2Tbav8MDGWBOsdH5AI8n9-qgQ12FAAggyZG4ZIg4cLdtb-6r-eO0f0n8CxVJrBuOgxxbUH-UiFd5ujaSp9HW3NkgKEK995IXaekcQdC3zfXmSN3dRuJPEfeUD-SrrUxlO8ghS0F4iaHgVn9l66_xFp7s20lruHRBSZJ2xCMqCVyWR5BUWJmcL5QMIWJiYz1GoH6Pu_9ISDnxYMu6s');
    http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    http.setRequestHeader('Access-Control-Allow-Origin', 'http://localhost:4000');
    http.send(data);
    socket.emit('lastKnownLocation', item);
    count++;
  }
}, 5000);
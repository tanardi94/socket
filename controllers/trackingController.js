const models = require('../models/index');
const response = require('../res');
const connection = require('../conn');
const auth = require('../helpers/auth');
const trackFunction = require('../helpers/trackFunctions');
const axios = require('axios');
const Socketio = require('socket.io');
const redis = require('redis');
const client  = redis.createClient();

exports.usingRedis = (req, res) => {
    var uniqueKeys = req.body.key
    var latitude = req.body.latitude
    var longitude = req.body.longitude

    var errors = []
    if(typeof uniqueKeys === 'undefined') {
        errors.push("Parameter key harus diisi")
    }

    if(typeof latitude === 'undefined') {
        errors.push("Parameter latitude harus diisi")
    }

    if(typeof longitude === 'undefined') {
        errors.push("Parameter longitude harus diisi")
    }

    if(errors.length > 0) {
        response.invalidParameter(errors, res)
    }

    Socketio.on("connection", socket => {
        // for(let i = 0; i < markers.length; i++) {
        //     socket.emit("marker", markers[i]);
    
        // }
        socket.on(uniqueKeys, data => {
            // markers.push(data);
            // console.log(data);
            client.set(uniqueKeys, latitude + ", " + longitude);
    
            client.get(uniqueKeys, (err, replies) => {
                if(err) {
                    response.failure(err, res);
                } else {
                    response.ok(replies, res);
                }
            });
            // Socketio.emit("marker", data);
        });
    });

    

}

exports.trying = (req, res) => {
    if(typeof req.query.view_uid !== 'undefined') {
        // response.ok(req.query, res);
        trackFunction.getApp(req.query.view_uid, (app) => {
            if(app !== false) {
                response.ok(app, res);
            } else {
                response.notFound("App", res);
            }
        });
    } else {
        response.invalidParameter("Parameter View UID tidak ada", res);
    }
}

exports.driver_update_location = (req, res) => {
    var bearerToken = req.headers.authorization;
    if(typeof bearerToken === 'undefined') {
        response.unauthenticated(res);
    }
    var driverLat = req.body.lat;
    var driverLng = req.body.lng;

    auth.User(bearerToken.split(' ')[1], (user) => {
        if(user != false) {
            if(typeof driverLat !== 'undefined' && typeof driverLng !== 'undefined') {
                trackFunction.updateDriverLocation(driverLat, driverLng, user.id, (account) => {
                    if(account != false) {
                        response.ok(account, res);
                    } else {
                        response.notFound("Data");
                    }
                });
            } else {
                trackFunction.getDriverLocation(user.id, (account) => {
                    if(account != false) {
                        response.ok(account, res);
                    } else {
                        response.notFound("User");
                    }
                })
            }
        } else {
            response.notFound('user', res);
        }
    });


}

exports.update_order_location = (req, res) => {
    var bearerToken = req.headers.authorization;
    if(typeof bearerToken === 'undefined') {
        response.unauthenticated(res);
    }

    if(typeof req.body.lat === 'undefined' || typeof req.body.lng === 'undefined') {
        response.invalidParameter("Parameter tidak lengkap", res);
    }

    var lat = req.body.lat;
    var lng = req.body.lng;
    // var order_unique_id = req.body.unique_id;

    auth.User(bearerToken.split(' ')[1], (user) => {
        if(user != false) {
            trackFunction.locationUpdate(user, lat, lng);
            response.ok("Order Location Updated", res);

            trackFunction.getOrderLocation(user.id, unique_id, (order) => {
                if(order != false) {
                    response.ok(order, res);
                } else {
                    response.failure("Failed Process", res);
                }
            });

        } else {
            response.failure("Failed to save", res);
        }
        // } else {
        //     response.notFound('user', res);
        // }

    });
}

exports.driverUpdateOrder = (req, res) => {
    var bearerToken = req.headers.authorization;
    if(typeof bearerToken === 'undefined') {
        response.unauthenticated(res);
    }

    auth.User(bearerToken.split(' ')[1], (user) => {
        if(user != false) {
            if(typeof req.body.driver_order === 'undefined') {
                response.invalidParameter("Parameter tidak lengkap", res);
            }
            var driver_order = req.body.driver_order;

            if(typeof req.body.driver_auto_accept !== 'undefined') {
                var driver_auto_accept = req.body.driver_auto_accept;
            } else {
                var driver_auto_accept = 0;
            }

            trackFunction.updateDriverOrder(driver_order, user.id, driver_auto_accept, (resp) => {
                if(typeof req.body.driver_lat !== 'undefined' && typeof req.body.driver_lng !== 'undefined') {
                    trackFunction.updateDriverLocation(req.body.driver_lat, req.body.driver_lng, user.id, (account) => {
                        if(account !== false) {
                            resp['result'] = account;
                            resp['message'] = "Informasi telah diperbarui";
                            response.ok(resp, res);
                        }
                    });
                } else {
                    trackFunction.getDriverLocation(user.id, (account) => {
                        if(account !== false) {
                            resp['result'] = account;
                            resp['message'] = "Informasi telah diperbarui";
                            response.ok(resp, res);
                        }
                    });
                }
            });
            
        }
    })
}

exports.tryingLocationInterval = (req, res) => {
    var bearerToken = req.headers.authorization;
    if(typeof bearerToken === 'undefined') {
        response.unauthenticated(res);
    }

    var longlats = [[-7.276343, 112.688602],
        [-7.276902, 112.688999],
        [-7.277647, 112.688092],
        [-7.277684, 112.688859],
        [-7.275561, 112.688907]];
    var count = 0;
    var item = {};
    item.Coordinate = {};

    auth.User(bearerToken.split(' ')[1], (user) => {    
        setInterval(function() {
            item.Coordinate.Longitude = longlats[count][1];
            item.Coordinate.Latitude = longlats[count][0];
            count++;
            axios.post('https://localhost:3000/api/track', {
                'lat': item.Coordinate.Latitude, 
                'lng': item.Coordinate.Longitude
            });
            // updateCoordinate(item);
          }, 5000);
          response.ok("successfully done", res);
    })
}
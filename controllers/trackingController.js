const models = require('../models/index');
const response = require('../res');
const connection = require('../conn');
const auth = require('../helpers/auth');
const trackFunction = require('../helpers/trackFunctions');
const axios = require('axios');

exports.trying = (req, res) => {
    if(req.params.app_id !== 'undefined') {
        var app_id = req.params.app_id;
        var app = trackFunction.getApp(app_id);
        response.ok(app, res);
    } else {
        response.notFound("App", res);
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
            trackFunction.getApp(req.body.appuid, (app) => {
                if(app === false) {
                    response.notFound("App", res);
                }
                if(typeof driverLat !== 'undefined' && typeof driverLng !== 'undefined') {
                    trackFunction.updateDriverLocation(driverLat, driverLng, user.id, (account) => {
                        if(account != false) {
                            response.ok(account, res);
                        } else {
                            response.notFound("User");
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
            });
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



    var lat = req.body.lat;
    var lng = req.body.lng;
    // var order_unique_id = req.body.unique_id;

    auth.User(bearerToken.split(' ')[1], (user) => {
        if(user != false) {
            trackFunction.locationUpdate(user, lat, lng);
            return response.ok("Good", res);

        }
        //     trackFunction.getOrderLocation(user.id, unique_id, (order) => {
        //         if(order != false) {
        //             response.ok(order, res);
        //         } else {
        //             response.failure("Failed Process", res);
        //         }
        //     });
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
            if(typeof req.body.driver_order !== 'undefined') {
                var driver_order = req.body.driver_order;
                if(typeof req.body.appuid !== 'undefined') {
                    var app_id = trackFunction.getApp(req.body.appuid);
                } else {
                    var app_id = -1;
                }

                if(typeof req.body.driver_auto_accept !== 'undefined') {
                    var driver_auto_accept = req.body.driver_auto_accept;
                } else {
                    var driver_auto_accept = 0;
                }

                var response = trackFunction.updateDriverOrder(driver_order, user.id, driver_auto_accept);
                
                if(typeof req.body.driver_lat !== 'undefined' && typeof req.body.driver_lng !== 'undefined') {
                    var account = trackFunction.updateDriverLocation(req.body.driver_lat, req.body.driver_lng, user.id);
                } else {
                    var account = trackFunction.getDriverLocation(user.id);
                }

            }
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
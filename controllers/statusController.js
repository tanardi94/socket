'use strict';

var response = require('../res');
// var connection = require('../conn');
const models = require('../models/index');

exports.all = function(req, res) {
    models.Status.findAll().then(status => {
        response.ok(status, res);
    });
};

exports.show = (req, res) => {
    
    models.Status.findOne({ where: { status_id: req.params.id } }).then(status => {
        if(!status) {
            response.notFound('Status', res);
        } else {
            response.ok(status, res);
        }
    });

}

exports.new = (req, res) => {
    
    models.Status.create({ s_text: req.body.text }).then(status => {
        response.ok(status, res);
    });

}

exports.index = function(req, res) {
    response.ok("Hello from the Node JS RESTful side!", res)
};

exports.update = (req, res) => {
    let text = req.body.text;
    let statusID = req.params.id;
    
    models.Status.update({ s_text: text }, {
        where: {
            status_id: statusID
        }
    }).then(status => {
        response.ok(status, res);
    }).catch(err => {
        response.failure(err);
    });
};
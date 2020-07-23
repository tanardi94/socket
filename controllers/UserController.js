const connection = require('../conn');
const response = require('../res');
const models = require('../models/index');
const uuid = require('uuid');

exports.index = (req, res) => {
    connection.query('SELECT * FROM users where status = 10', (error, results, fields) => {
        if(error) response.failure(error, res);
        
        if(results.length < 1) {
            response.notFound("User", res);
        } else {
            response.ok(results, res);
        }
    });
};

exports.view = (req, res) => {
    connection.query("SELECT * from users where status = 10 and id = ?", [req.params.id], (error, results, fields) => {
        if(error) response.failure(error, res);
        
        if(results.length < 1) {
            response.notFound("User", res);
        } else {
            response.ok(results, res);
        }
        
    });
};

exports.create = (req, res) => {
    
    models.User.create({
        name: req.body.name,
        encrpted_password: req.body.password,
        email: req.body.email,
        unique_id: uuid.v4(),
        view_uid: uuid.v4()
    }).then(user => {
        response.created(user, res);
    }).catch(err => {
        response.failure(err, res);
    });
};
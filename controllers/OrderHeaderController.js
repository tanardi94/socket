const models = require('../models/index');
const response = require('../res');

function authenticate(token) {
    return new Promise((resolve, reject) => {
        models.UsersToken.findAll({ where: { bearer_token: token}, attributes: ['user_id', 'bearer_token', 'access_token'] }).then((user, res) => {
            if(!user) {
                response.notFound("User", res);
            } else {
                resolve(user[0]);
            }
        });
    }).catch((err) => {
        reject(err);
    });
}


exports.index = (req, res) => {

    models.OrderHeader.findAll({ where: { status: 1, confirmation_admin: 1 }, attributes: ['unique_id', 'view_uid', 'customer_id', 'supplier_id', 'total', 'order_status', 'order_no'] }).then(order => {
        response.ok(order[0], res);
    });

};

exports.show = (req, res) => {
    
    models.OrderHeader.findByPk(req.params.id).then(order => {
        if(!order) {
            response.notFound('Order');
        } else {
            response.ok(order, res);
        }
    })

};


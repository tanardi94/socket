var Sequelize = require("sequelize");
var status = require("./status");
var users = require('./users');
var connConfig = require('../config/database');
var jagel = require('../config/jageldb');
var order_header = require('./orderHeader');
var users_token = require('./usersToken');
var sequelize = new Sequelize(connConfig.DB, connConfig.USER, connConfig.PASSWORD, {
    host: connConfig.HOST,
    dialect: connConfig.dialect,
});

var sequelizeJagel = new Sequelize(jagel.DB, jagel.USER, jagel.PASSWORD, {
    host: jagel.HOST,
    dialect: jagel.dialect,
    timezone: '+07:00',
    logging: false
});

module.exports = {
    Status: status(sequelize, Sequelize.DataTypes),
    User: users(sequelizeJagel, Sequelize),
    OrderHeader: order_header(sequelizeJagel, Sequelize),
    UsersToken: users_token(sequelizeJagel, Sequelize)
};
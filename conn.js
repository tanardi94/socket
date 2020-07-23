const mysql = require('mysql');


var dbConn = mysql.createPool({
    connectionLimit   :   100,
    host              :   '13.229.232.107',
    user              :   'ardi',
    password          :   'P4ssword!',
    database          :   'jagel_dev',
    debug             :   false
});

module.exports = dbConn;
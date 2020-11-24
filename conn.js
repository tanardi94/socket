const mysql = require('mysql');


var dbConn = mysql.createPool({
    connectionLimit   :   100,
    host              :   '54.254.61.62',
    user              :   'ardi',
    password          :   '4rdideW4!',
    database          :   'jagel_dev',
    debug             :   false
});

module.exports = dbConn;
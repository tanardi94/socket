const connection = require('../conn');

exports.User = (token, callback) =>  {
    connection.query("SELECT user_id from users_token where status = 1 and bearer_token = ?", token, (err, results, fields) => {

        if(err || results.length < 1) {
            return callback(false);
        } else {
            connection.query("SELECT * from users where status = 10 and id = ?", results[0].user_id, (error, result, fields) => {
                
                if(error || result.length < 1) {
                    return callback(false);
                } else {
                    return callback(result[0]);
                }
            });
        }
    });
}
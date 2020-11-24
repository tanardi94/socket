const connection = require('../conn');

exports.locationUpdate = (user, lat, lng) => {
    connection.query("UPDATE users SET driver_lat =?, driver_lng =?, last_update_date = NOW(), last_updated_by =? WHERE id =?", [lat, lng, user.id, user.id], (error, result, fields) => {
        if(error) return false;
        return true;
    });
}

exports.getOrderLocation = (user, unique_id, callback) => {
    connection.query("SELECT oh.courrier_type, driver.driver_lat driver_lat, driver.driver_lng driver_lng, IFNULL(customer.driver_lat, ol.customer_lat) customer_lat, IFNULL(customer.driver_lng, ol.customer_lng) customer_lng, if(oh.driver_assigned is not null, 1, 0) driver_assigned_flag, (select aci.icon from app_courrier_icon aci where aci.status=1 and aci.app_id=app.id and aci.courrier_type=oh.courrier_type AND oh.driver_flag=1 limit 1) driver_icon, (select aci.operator from app_courrier_icon aci where aci.status=1 and aci.app_id=app.id and aci.courrier_type=oh.courrier_type AND oh.driver_flag=1 limit 1) driver_operator FROM order_header oh LEFT JOIN users customer ON customer.id=oh.customer_id LEFT JOIN app ON app.id=oh.supplier_id AND app.status=1 LEFT JOIN users supplier ON supplier.id=app.owner LEFT JOIN users driver ON driver.id=oh.driver_assigned LEFT JOIN order_line ol ON ol.header_id=oh.id AND ol.category=2 WHERE oh.unique_id=? AND oh.status=1 AND oh.order_status=0 AND oh.driver_flag=1 AND (driver.id = ? OR customer.id=? OR supplier.id=? OR (oh.driver_assigned is null AND ? in (select driver2.id from users driver2 where driver2.app_id = oh.supplier_id and driver2.driver_status=2 and oh.courrier_type=driver2.driver_type)) OR app.id in (select admin.app_id from users admin where (admin.admin_status=1 OR admin.admin_status=2) AND admin.status=10 AND admin.id=?))", [unique_id, user.id, user.id, user.id, user.id, user.id], (error, result, fields) => {
        if(error) {
            return callback(false);
        } else {
            return callback(result);
        }
    });
}

exports.getApp = (view_uid, callback) => {
    connection.query("SELECT id, name, codename, view_uid, creation_date, last_update_date from app where status = 1 and view_uid = ?", view_uid, (error, result, fields) => {
        if(error || result.length < 1) {
            return callback(false);
        } else {
            return callback(result[0]);
        }
    });
}

exports.updateDriverLocation = (driverLat, driverLng, user_id, callback) => {
    connection.query("UPDATE users SET driver_lat=?, driver_lng=?, last_update_date=NOW(), last_updated_by=? WHERE id=?", [driverLat, driverLng, user_id, user_id], (error, result, fields) => {
        if(error || result.length < 1) {
            return callback(false);
        } else {
            connection.query("SELECT users.view_uid, users.name, users.driver_lat, users.driver_lng FROM users WHERE id = ? ", user_id, (error, result, fields) => {
                if(error || result.length < 1) {
                    return callback(false);
                } else {
                    return callback(result);
                }
            })
        }
    });
}

exports.getDriverLocation = (user_id, callback) => {
    connection.query("SELECT users.view_uid, users.name, users.driver_lat, users.driver_lng FROM users WHERE id = ?", user_id, (error, result, fields) => {
        if(error || result.length < 1) {
            return callback(false);
        } else {
            return callback(result);
        }
    })
}

exports.updateDriverOrder = (driver_order, user_id, driver_auto_accept = 0, callback) => {
    connection.query("UPDATE users SET driver_order = ?, driver_auto_accept = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?", [driver_order, driver_auto_accept,user_id, user_id], (error, result, fields) => {
        if(error || result.length < 1) {
            return callback(false);
        } else {
            connection.query("SELECT view_uid, name, driver_order, driver_auto_accept FROM users WHERE id = ?", user_id, (error, result, fields) => {
                if(error || result.length < 1) {
                    return callback(false);
                } else {
                    return callback(result);
                }
            });
        }
    });
}
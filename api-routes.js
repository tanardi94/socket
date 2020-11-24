const router = require('express').Router();
const dbConn = require('./conn');
const status = require('../socket/controllers/statusController');
const user = require('../socket/controllers/UserController');
const orderHeader = require('../socket/controllers/OrderHeaderController');
const tracking = require('../socket/controllers/trackingController');

router.route('/user').get(user.index).post(user.create);
router.route('/user/:id').get(user.view);

router.route('/trying').get(tracking.trying).post(tracking.driver_update_location);
router.route('/status/:id').put(status.update).get(status.show);
router.route('/order').get(orderHeader.index);
router.route('/order/:id').get(orderHeader.show);
router.route('/track').post(tracking.usingRedis);
router.route('/updateOrder').post(tracking.driverUpdateOrder);
router.route('/trackcoba').get(tracking.tryingLocationInterval);


module.exports = router;
const router = require('express').Router();
const dbConn = require('./conn');
const status = require('../socket/controllers/statusController');
const user = require('../socket/controllers/UserController');
const orderHeader = require('../socket/controllers/OrderHeaderController');
const tracking = require('../socket/controllers/trackingController');

router.route('/user').get(user.index).post(user.create);
router.route('/user/:id').get(user.view);

router.route('/status').get(status.all).post(status.new);
router.route('/status/:id').put(status.update).get(status.show);
router.route('/order').get(orderHeader.index);
router.route('/order/:id').get(orderHeader.show);
router.route('/track').post(tracking.update_order_location);
router.route('/trackcoba').get(tracking.tryingLocationInterval);


module.exports = router;
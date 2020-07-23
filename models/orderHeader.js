/* jshint indent: 2 */

module.exports = function(sequelize, DataTypes) {
  var OrderHeader = sequelize.define('order_header', {
    id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      primaryKey: true,
      autoIncrement: true
    },
    unique_id: {
      type: DataTypes.STRING(40),
      allowNull: false,
      unique: true
    },
    view_uid: {
      type: DataTypes.STRING(40),
      allowNull: false,
      unique: true
    },
    customer_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      references: {
        model: 'users',
        key: 'id'
      }
    },
    supplier_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      references: {
        model: 'app',
        key: 'id'
      }
    },
    currency: {
      type: DataTypes.STRING(10),
      allowNull: false,
      defaultValue: 'IDR'
    },
    total: {
      type: "DOUBLE",
      allowNull: true,
      defaultValue: '0'
    },
    creation_date: {
      type: 'TIMESTAMP',
      defaultValue: DataTypes.literal('CURRENT_TIMESTAMP'),
      allowNull: false
    },
    created_by: {
      type: DataTypes.INTEGER(11),
      allowNull: false
    },
    last_update_date: {
      type: 'TIMESTAMP',
      defaultValue: DataTypes.literal('CURRENT_TIMESTAMP'),
      allowNull: false
    },
    last_updated_by: {
      type: DataTypes.INTEGER(11),
      allowNull: false
    },
    status: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      defaultValue: '1'
    },
    order_status: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      defaultValue: '0'
    },
    order_type_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      defaultValue: '0'
    },
    full_name: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    payment: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    phone_number: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    address: {
      type: DataTypes.STRING(500),
      allowNull: true
    },
    city: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    total_weight: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    service: {
      type: DataTypes.STRING(60),
      allowNull: true
    },
    shipping: {
      type: "DOUBLE",
      allowNull: true,
      defaultValue: '0'
    },
    etd: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    subtotal: {
      type: "DOUBLE",
      allowNull: true,
      defaultValue: '0'
    },
    use_expedition: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '1'
    },
    confirmation_code: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    confirmation_admin: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    waybill: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    note: {
      type: DataTypes.STRING(200),
      allowNull: true
    },
    discount: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    order_no: {
      type: DataTypes.STRING(30),
      allowNull: true,
      unique: true
    },
    withdrawal_flag: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    rating_quality: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    rating_accuracy: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    reject_note: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    province: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    district: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    district_name: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    city_name: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    province_name: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    expedition: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    review_flag: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    category: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    hour: {
      type: DataTypes.INTEGER(2),
      allowNull: true
    },
    minute: {
      type: DataTypes.INTEGER(2),
      allowNull: true
    },
    book_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    use_dropship_flag: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    dropship_name: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    dropship_phone: {
      type: DataTypes.STRING(30),
      allowNull: true
    },
    payment_type: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    use_confirmation_code: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    driver_flag: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    driver_assigned: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    driver_done: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    driver_find: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    courrier_type: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    partner_id: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '-1'
    },
    discount_code: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    discount_cashback: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    directions: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    discount_partner_id: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '-1'
    },
    api_flag: {
      type: DataTypes.INTEGER(4),
      allowNull: false,
      defaultValue: '0'
    },
    driver_adjust_price: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    driver_adjust_note: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    service_flag: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    }
  }, {
    tableName: 'order_header',
    timestamps: false
  });

  return OrderHeader;
};

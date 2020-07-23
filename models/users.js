/* jshint indent: 2 */

module.exports = function(sequelize, DataTypes) {
  return sequelize.define('users', {
    id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      primaryKey: true,
      autoIncrement: true
    },
    status: {
      type: DataTypes.INTEGER(4),
      allowNull: false,
      defaultValue: '10'
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
    username: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    name: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    email: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    bio: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    photo: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    encrypted_password: {
      type: DataTypes.STRING(80),
      allowNull: true
    },
    salt: {
      type: DataTypes.STRING(10),
      allowNull: true
    },
    auth_key: {
      type: DataTypes.STRING(32),
      allowNull: true
    },
    updated_password: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: 0
    },
    creation_date: {
      type: 'TIMESTAMP',
      defaultValue: DataTypes.literal('CURRENT_TIMESTAMP'),
      allowNull: false
    },
    created_by: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      defaultValue: '-1'
    },
    last_update_date: {
      type: 'TIMESTAMP',
      defaultValue: DataTypes.literal('CURRENT_TIMESTAMP'),
      allowNull: false
    },
    last_updated_by: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      defaultValue: '-1'
    },
    fb_id: {
      type: DataTypes.STRING(30),
      allowNull: true
    },
    gender: {
      type: DataTypes.STRING(10),
      allowNull: false,
      defaultValue: 'U'
    },
    google_id: {
      type: DataTypes.STRING(30),
      allowNull: true
    },
    token: {
      type: DataTypes.STRING(300),
      allowNull: true
    },
    password_reset_token: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    hide_explore: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '1'
    },
    language: {
      type: DataTypes.STRING(30),
      allowNull: true
    },
    register_from: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    subscribe: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '1'
    },
    subscribe_token: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    premium_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    silver_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    gold_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    platinum_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    app_id: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '-1'
    },
    phone: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    verified_email: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    verified_email_token: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    verified_email_type: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    referral_id: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    driver_status: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    driver_license_plate: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    driver_model: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    driver_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    driver_date_accept: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    driver_order: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '1'
    },
    driver_lat: {
      type: DataTypes.DECIMAL,
      allowNull: true
    },
    driver_lng: {
      type: DataTypes.DECIMAL,
      allowNull: true
    },
    driver_type: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    register_ip: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    verified_time: {
      type: DataTypes.DATE,
      allowNull: true
    },
    imei: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    partner_status: {
      type: DataTypes.INTEGER(4).UNSIGNED,
      allowNull: false,
      defaultValue: '0'
    },
    partner_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    partner_date_accept: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    partner_order: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    driver_points_flag: {
      type: DataTypes.INTEGER(4),
      allowNull: true
    },
    change_email_token: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    change_email: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    admin_status: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    suspend_date: {
      type: DataTypes.DATE,
      allowNull: true
    },
    pin_code: {
      type: DataTypes.STRING(6),
      allowNull: true
    },
    verified_phone: {
      type: DataTypes.INTEGER(4),
      allowNull: true,
      defaultValue: '0'
    },
    verified_phone_token: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    verified_phone_time: {
      type: DataTypes.DATE,
      allowNull: true
    },
    change_phone: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    change_phone_token: {
      type: DataTypes.INTEGER(11),
      allowNull: true
    },
    verified_identity: {
      type: DataTypes.INTEGER(4),
      allowNull: false,
      defaultValue: '0'
    },
    verified_identity_time: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    tableName: 'users',
    timestamps: false
  });
};

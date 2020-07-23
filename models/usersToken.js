
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('users_token', {
    id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      primaryKey: true,
      autoIncrement: true
    },
    user_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      references: {
        model: 'users',
        key: 'id'
      }
    },
    app_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false
    },
    token: {
      type: DataTypes.STRING(300),
      allowNull: true
    },
    access_token: {
      type: DataTypes.STRING(50),
      allowNull: false
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
    language: {
      type: DataTypes.STRING(30),
      allowNull: true
    },
    firebase_type: {
      type: DataTypes.INTEGER(11),
      allowNull: true,
      defaultValue: '0'
    },
    imei: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    ip_address: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    bearer_token: {
      type: DataTypes.TEXT(),
      allowNull: true
    }
  }, {
    tableName: 'users_token',
    timestamps: false
  });
};

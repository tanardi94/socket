/* jshint indent: 2 */

module.exports = function(sequelize, DataTypes) {
  return sequelize.define('status', {
    status_id: {
      type: DataTypes.INTEGER(11),
      allowNull: false,
      primaryKey: true,
      autoIncrement: true
    },
    s_text: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    t_status: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.fn('current_timestamp')
    }
  }, {
    tableName: 'status',
    timestamps:false
  });
};

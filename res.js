'use strict';

exports.ok = function(values, res) {
  var data = {
      'success': true,
      'values': values
  };
  res.statusCode = 200;
  res.json(data);
  res.end();
};

exports.customResponse = (message, status, res) => {
  var data = {
    'success': status,
    'message': message
  };
  res.statusCode = 200;
  res.json(data);
  res.end();
}

exports.failure = function(message, res) {
  var data = {
    'success': false,
    'message': message
  };
  res.statusCode = 400;
  res.json(data);
  res.end();
};

exports.notFound = function(message, res) {
  var data = {
    'success': false,
    'message': message + " tidak ditemukan"
  };
  res.statusCode = 404;
  res.json(data);
  res.end();
};

exports.created = function(message, res) {
  var data = {
    'success': true,
    'message': message + " is successfully created"
  };
  res.statusCode = 201;
  res.json(data);
  res.end();
}

exports.invalidParameter = function(message, res) {
  var data = {
    'success': false,
    'message': message
  };
  res.statusCode = 422;
  res.json(data);
  res.end();
}

exports.forbidden = function(res) {
  var data = {
    'success': false,
    'message': "You have no access for this service"
  };

  res.statusCode = 403;
  res.json(data);
  res.end();
}

exports.unauthenticated = function(res) {
  var data = {
    'success': false,
    'message': "You are unauthenticated"
  };

  res.statusCode = 401;
  res.json(data);
  res.end();
}
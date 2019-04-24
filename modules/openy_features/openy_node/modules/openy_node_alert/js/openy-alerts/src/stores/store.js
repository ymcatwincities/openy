const store =
  process.env.NODE_ENV === 'production' ? 'store.prod' : 'store.dev';
module.exports = require(`./${store}`);

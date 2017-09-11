var merge = require('webpack-merge')
var prodEnv = require('./prod.env')

module.exports = merge(prodEnv, {
  NODE_ENV: '"development"',
  TOKEN_COOKIE_NAME: '"vueBlogToken"'
})

export default {
  get: function (key) {
    let list = []
    let cookies = {}
    let hasCookies = false

    try {
      const all = document.cookie
      if (all) {
        list = all.split('; ')
      }
    } catch (e) {}

    for (let i = 0; i < list.length; ++i) {
      if (list[i]) {
        let cookie = list[i]
        let pos = cookie.indexOf('=')
        let name = cookie.substring(0, pos)
        let value = decodeURIComponent(cookie.substring(pos + 1))
        if (typeof value === 'undefined' || value === null) {
          continue
        }

        if (key === undefined || key === name) {
          try {
            cookies[name] = JSON.parse(value)
          } catch (e) {
            cookies[name] = value
          }
          if (key === name) {
            return cookies[name]
          }
          hasCookies = true
        }
      }
    }
    if (hasCookies && key === undefined) {
      return cookies
    } else {
      return undefined
    }
  },
  set: function (key, value, options) {
    options = Object.assign({
      expires: new Date('Thu, 01 Jan 1970 00:00:00 GMT'),
      expirationUnit: 'seconds',
      path: '/',
      domain: window.location.hostname,
      secure: false
    }, options)

    if (typeof value !== 'undefined') {
      value = typeof value === 'object' ? JSON.stringify(value) : String(value)
      if (typeof options.expires === 'number') {
        let expiresFor = options.expires
        options.expires = new Date()
        if (expiresFor === -1) {
          options.expires = new Date('Thu, 01 Jan 1970 00:00:00 GMT')
        } else if (typeof options.expirationUnit !== 'undefined') {
          if (options.expirationUnit === 'hours') {
            options.expires.setHours(options.expires.getHours() + expiresFor)
          } else if (options.expirationUnit === 'minutes') {
            options.expires.setMinutes(options.expires.getMinutes() + expiresFor)
          } else if (options.expirationUnit === 'seconds') {
            options.expires.setSeconds(options.expires.getSeconds() + expiresFor)
          } else if (options.expirationUnit === 'milliseconds') {
            options.expires.setMilliseconds(options.expires.getMilliseconds() + expiresFor)
          } else {
            options.expires.setDate(options.expires.getDate() + expiresFor)
          }
        } else {
          options.expires.setDate(options.expires.getDate() + expiresFor)
        }

        document.cookie = [
          encodeURIComponent(key),
          '=',
          encodeURIComponent(value),
          options.expires ? '; Expires=' + options.expires.toUTCString() : '',
          options.path ? '; Path=' + options.path : '',
          options.domain ? '; Domain=' + options.domain : '',
          options.secure ? '; Secure' : ''
        ].join('')
      }
    }
  },
  remove: function (key, options) {
    const hasCookie = this.get(key) !== undefined
    if (hasCookie) {
      if (typeof options !== 'object') {
        options = {}
      }
      options.expires = -1
      this.set(key, '', options)
    }
    return hasCookie
  }
}

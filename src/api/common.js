import cookies from '@/utilities/cookies'
const Promise = window.Promise || require('promise-polyfill')

const getToken = () => {
  const token = cookies.get(process.env.TOKEN_COOKIE_NAME)
  if (typeof token === 'undefined') {
    return Promise.reject({
      status: 'error',
      messages: 'Token does not exists'
    })
  }
  return Promise.resolve(token)
}
const generateResponse = (res) => {
  let messages = ''
  if (typeof res.data !== 'undefined' && (res.status >= 200 && res.status < 300)) {
    messages = res.data.messages || ''
    if (typeof res.data.status !== 'undefined' && res.data.status === 'ok') {
      if (typeof res.data.token !== 'undefined' && res.data.token !== '') {
        cookies.set(process.env.TOKEN_COOKIE_NAME, res.data.token, { expires: 86400 })
      }
      return Promise.resolve(res.data)
    }
  }

  return Promise.reject({
    status: 'err',
    messages: messages,
    state: res.status
  })
}
const generateErrorResponse = (err) => {
  if (typeof err.data !== 'undefined') {
    let messages = err.data.messages || ''

    if (err.status === 404) {
      return Promise.reject({
        status: 'err',
        code: err.status,
        messages: messages,
        results: []
      })
    } else {
      return Promise.reject({
        status: 'err',
        code: err.status,
        messages: messages
      })
    }
  } else {
    return Promise.reject({
      status: 'err',
      code: err.status,
      messages: ''
    })
  }
}

export { generateResponse, generateErrorResponse, getToken }

import axios from 'axios'
import { generateResponse, generateErrorResponse, getToken } from './common'
const Promise = window.Promise || require('promise-polyfill')
const API_END_POINT = '/api'

export default {
  upload (data) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/fileuploader',
        method: 'post',
        data: data,
        headers: {
          Authorization: 'Bearer ' + token,
          'Content-Type': 'multipart/form-data'
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      Promise.reject(err)
    })
  }
}

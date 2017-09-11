import axios from 'axios'
import { generateResponse, generateErrorResponse, getToken } from './common'
const Promise = window.Promise || require('promise-polyfill')
const API_END_POINT = '/api'

export default {
  checkAuth () {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/auth_check',
        method: 'get',
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  register (data) {
    return axios({
      url: API_END_POINT + '/signup',
      method: 'post',
      data: data
    }).then(generateResponse, generateErrorResponse)
  },
  login (data) {
    return axios({
      url: API_END_POINT + '/signin',
      method: 'post',
      data: data
    }).then(generateResponse, generateErrorResponse)
  },
  getProfile (data) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/profile',
        method: 'get',
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  updateProfile (data) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/profile',
        method: 'post',
        data: data,
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  changePassword (data) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/change_password',
        method: 'post',
        data: data,
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  }
}

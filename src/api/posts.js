import axios from 'axios'
import { generateResponse, generateErrorResponse, getToken } from './common'
const Promise = window.Promise || require('promise-polyfill')
const API_END_POINT = '/api'

export default {
  fetchPosts (params = { page: 1 }) {
    return axios({
      url: API_END_POINT + '/',
      method: 'get',
      params: params
    }).then(generateResponse, generateErrorResponse)
  },
  fetchMyPost (id) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/my/entry/' + id,
        method: 'get',
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  fetchMyPosts (params = { page: 1 }) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/my/entries',
        method: 'get',
        params: params,
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  searchPosts (params = { page: 1 }) {
    return axios({
      url: API_END_POINT + '/search',
      method: 'get',
      params: params
    }).then(generateResponse, generateErrorResponse)
  },
  fetchPostByAlias (alias) {
    return axios({
      url: API_END_POINT + '/entry/' + alias,
      method: 'get'
    }).then(generateResponse, generateErrorResponse)
  },
  addPost (data = []) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/entry/add',
        method: 'put',
        data: data,
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  },
  updatePost (data = []) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/entry/update',
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
  deletePost (postId) {
    return getToken().then(token => {
      return axios({
        url: API_END_POINT + '/entry/delete',
        method: 'delete',
        data: {
          id: postId
        },
        headers: {
          Authorization: 'Bearer ' + token
        }
      }).then(generateResponse, generateErrorResponse)
    }, err => {
      return Promise.reject(err)
    })
  }
}

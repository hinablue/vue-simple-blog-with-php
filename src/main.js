import Vue from 'vue'
import App from './App'
import router from './router'
import swal from 'sweetalert2'

Vue.config.productionTip = false
Vue.prototype.$swal = swal

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  render: h => h(App)
})

import Vue from 'vue'
import App from './App'
import router from './router'
import swal from 'sweetalert2'
import BootstrapVue from 'bootstrap-vue'

Vue.config.productionTip = false
Vue.prototype.$swal = swal
Vue.use(BootstrapVue)

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  render: h => h(App)
})

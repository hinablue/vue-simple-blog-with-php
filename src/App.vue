<template lang="pug">
  #app
    b-container.main(fluid)
      router-view
</template>

<script>
import 'bootstrap-vue/dist/bootstrap-vue.css'
import 'bootstrap/dist/css/bootstrap.css'
import 'mdi/css/materialdesignicons.min.css'
import 'sweetalert2/dist/sweetalert2.min.css'

import { bContainer } from 'bootstrap-vue/lib/components'

export default {
  name: 'app',
  components: {
    bContainer: bContainer
  }
}
</script>

<style>
.pointer {
  cursor: pointer;
}
</style>

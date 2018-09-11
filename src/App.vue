<template lang="pug">
  #app
    .main
      router-view
</template>

<script>
import 'bootstrap-vue/dist/bootstrap-vue.css'
import 'bootstrap/dist/css/bootstrap.css'
import 'mdi/css/materialdesignicons.min.css'
import 'sweetalert2/dist/sweetalert2.min.css'

export default {
  name: 'app'
}
</script>

<style>
.pointer {
  cursor: pointer;
}
</style>

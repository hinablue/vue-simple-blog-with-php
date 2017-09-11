<template lang="pug">
  section.my
    router-view
</template>

<script>
import usersAPI from '@/api/users'
import { bContainer } from 'bootstrap-vue/lib/components'

export default {
  name: 'owner',
  components: {
    bContainer: bContainer
  },
  beforeRouteEnter (to, from, next) {
    usersAPI.checkAuth()
      .then(() => {
        next()
      }, () => {
        next({ name: 'Login' })
      })
  }
}
</script>

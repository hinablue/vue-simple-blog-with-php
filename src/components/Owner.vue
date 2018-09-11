<template lang="pug">
  section.my
    router-view
</template>

<script>
import usersAPI from '@/api/users'

export default {
  name: 'owner',
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

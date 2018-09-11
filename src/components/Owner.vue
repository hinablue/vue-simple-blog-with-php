<template lang="pug">
  section.my
    b-navbar(variant="info", type="dark")
      b-navbar-brand(tag="h1", :to="{ name: 'Homepage' }")
        small.pl-2 Blog
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

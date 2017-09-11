<script>
import cookies from '@/utilities/cookies'

export default {
  name: 'logout',
  beforeRouteEnter (to, from, next) {
    cookies.remove(process.env.TOKEN_COOKIE_NAME)
    next({ name: 'Homepage' })
  }
}
</script>

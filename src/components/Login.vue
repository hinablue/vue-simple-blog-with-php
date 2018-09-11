<template lang="pug">
  b-row.justify-content-md-center.login
    b-col(cols="4")
      h4 Login
      b-form
        b-form-group(label="E-mail", label-for="email")
          b-form-input(id="email", type="email", v-model.trim="form.email", required, placeholder="Your email")
        b-form-group(label="Password", label-for="password")
          b-form-input(id="password", type="password", v-model.trim="form.password", required, placeholder="Your password")
        b-button.btn.btn-success(type="button", @click.prevent="login") Login
</template>

<script>
import usersAPI from '@/api/users'

export default {
  name: 'login',
  methods: {
    login () {
      if (this.form.email === '') {
        this.$swal({
          type: 'error',
          title: 'Oops!',
          text: 'Please fill the e-mail account'
        })
        return false
      }
      if (this.form.password === '') {
        this.$swal({
          type: 'error',
          title: 'Oops!',
          text: 'Please fill your password'
        })
        return false
      }

      usersAPI.login(this.form)
        .then(res => {
          this.$router.push({
            name: 'Entries'
          })
        }, err => {
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: err.messages
          })
        })
    }
  },
  data () {
    return {
      form: {
        email: '',
        password: ''
      }
    }
  }
}
</script>

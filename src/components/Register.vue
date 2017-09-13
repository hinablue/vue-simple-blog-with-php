<template lang="pug">
  b-row.justify-content-md-center.register
    b-col(cols="4")
      h4 Signup
      b-form
        b-form-group(label="Avatar", label-for="avatar")
          b-img(v-show="previewAvatar", :src="avatarImage", fluid, alt="avatar")
          b-form-input(id="avatar", type="file", v-model="form.avatar", required, accept="image/*", placeholder="Your avatar")
        b-form-group(label="Name", label-for="name")
          b-form-input(id="name", type="text", v-model.trim="form.name", required, placeholder="Your name")
        b-form-group(label="E-mail", label-for="email")
          b-form-input(id="email", type="email", v-model.trim="form.email", required, placeholder="Your email")
        b-form-group(label="Password", label-for="password")
          b-form-input(id="password", type="password", v-model.trim="form.password", required, placeholder="Your password")
        b-button.btn.btn-success(type="button", @click.prevent="register") Signup
</template>

<script>
import usersAPI from '@/api/users'
import uploaderAPI from '@/api/uploader'
import {
  bRow,
  bCol,
  bImg,
  bForm,
  bFormInput,
  bFormGroup,
  bButton
} from 'bootstrap-vue/lib/components'

export default {
  name: 'register',
  components: {
    bRow: bRow,
    bCol: bCol,
    bImg: bImg,
    bForm: bForm,
    bFormInput: bFormInput,
    bFormGroup: bFormGroup,
    bButton: bButton
  },
  created () {
    this.$watch('form.avatar', () => {
      this.previewAvatar = false
      let avatar = document.getElementById('avatar')
      if (avatar.files.length > 0) {
        const file = avatar.files[0]
        if (file.size > 5 * 1024 * 1024 || file.size === 0) {
          this.avatarFileError = true
          return false
        }

        if (!/image\/(png|jpeg|gif)/i.test(file.type)) {
          this.avatarFileError = true
          return false
        }

        let fileReader = new FileReader()
        fileReader.readAsDataURL(file)
        fileReader.onload = e => {
          this.avatarImage = e.target.result
          this.previewAvatar = true
        }
      }
    })
  },
  methods: {
    register () {
      if (this.avatarFileError || !this.previewAvatar) {
        this.$swal({
          type: 'error',
          title: 'Oops!',
          text: 'Please pick up the avatar image'
        })
        return false
      }
      if (this.form.name === '') {
        this.$swal({
          type: 'error',
          title: 'Oops!',
          text: 'Please fill your name'
        })
        return false
      }
      if (this.form.email === '') {
        this.$swal({
          type: 'error',
          title: 'Oops!',
          text: 'Please fill your e-mail account'
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

      usersAPI.register(this.form)
        .then(() => {
          let data = new FormData()
          data.append('isAvatar', true)
          data.append('file', document.getElementById('avatar').files[0])
          uploaderAPI.upload(data)
            .then(res => {
              this.$router.push({
                name: 'Entries'
              })
            }, err => {
              this.$swal({
                type: 'warning',
                title: 'Oops!',
                text: err.messages,
                showCancelButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                confirmButtonText: 'Next'
              }).then(() => {
                this.$router.push({
                  name: 'Entries'
                })
              })
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
        name: '',
        email: '',
        password: '',
        avatar: ''
      },
      previewAvatar: false,
      avatarImage: '',
      avatarFileError: false
    }
  }
}
</script>

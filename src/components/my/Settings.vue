<template lang="pug">
  b-row.justify-content-md-center.register
    b-col(cols="6")
      h4 Settings
      hr
      b-form
        h5 Profile
        b-form-group(label="Avatar", label-for="avatar")
          .user-avatar(v-show="!previewAvatar", :style="avatarStyles")
          b-img(v-show="previewAvatar", :src="avatarImage", fluid, alt="avatar")
          b-form-input(id="avatar", type="file", v-model="form.avatar", required, accept="image/*", placeholder="Your avatar")
        b-form-group(label="Name", label-for="name")
          b-form-input(id="name", type="text", v-model.trim="form.name", required, placeholder="Your name")
        b-form-group(label="E-mail", label-for="email")
          b-form-input(id="email", type="email", v-model.trim="form.email", required, placeholder="Your email")
        b-button.btn.btn-success(type="button", @click.prevent="save") Save

      hr

      b-form
        h5 Change Password
        b-form-group(label="Old Password", label-for="old-password")
          b-form-input(id="old-password", type="password", v-model.trim="password.oldPassword", required, placeholder="Your old password")
        b-form-group(label="New Password", label-for="new-password")
          b-form-input(id="new-password", type="password", v-model.trim="password.newPassword", required, placeholder="Your new password")
        b-form-group(label="Repeat New Password", label-for="repeat-password")
          b-form-input(id="repeat-password", type="password", v-model.trim="password.repeatPassword", required, placeholder="Repeat your new password")
        b-button.btn.btn-success(type="button", @click.prevent="changePassword") Change
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
  beforeRouteEnter (to, from, next) {
    usersAPI.getProfile()
      .then(res => {
        next(vm => {
          vm.form.name = res.results.name
          vm.form.email = res.results.email
          vm.oldAvatarImage = res.results.avatar
        })
      }, () => {
        next({ name: 'Signin' })
      })
  },
  created () {
    this.$watch('form.avatar', () => {
      this.previewAvatar = false
      let avatar = document.getElementById('avatar')
      if (avatar.files.length > 0) {
        const file = avatar.files[0]
        if (file.size > 5 * 1024 * 1024 || file.size === 0) {
          this.avatarFileError = true
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: 'File size is too large (5MB)'
          })
          return false
        }

        if (!/image\/(png|jpeg|gif)/i.test(file.type)) {
          this.avatarFileError = true
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: 'File type is not an image (png/jpg/gif)'
          })
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
  computed: {
    avatarStyles () {
      return {
        backgroundImage: 'url(' + this.oldAvatarImage + ')'
      }
    }
  },
  methods: {
    save () {
      if (this.avatarFileError) {
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

      usersAPI.updateProfile(this.form)
        .then(res => {
          if (document.getElementById('avatar').files.length > 0) {
            let data = new FormData()
            data.append('user_id', res.results.id)
            data.append('isAvatar', true)
            data.append('file', document.getElementById('avatar').files[0])
            uploaderAPI.upload(data)
              .then(res => {
                this.$swal({
                  type: 'success',
                  title: 'OK'
                })
              }, err => {
                this.$swal({
                  type: 'warning',
                  title: 'Oops!',
                  text: err.messages
                })
              })
          }
        }, err => {
          this.$swal({
            type: 'warning',
            title: 'Oops!',
            text: err.messages
          })
        })
    },
    changePassword () {
      if (this.password.oldPassword === '') {
        this.$swal({
          type: 'warning',
          title: 'Oops!',
          text: 'Please fill your old password'
        })
        return false
      }
      if (this.password.newPassword === '') {
        this.$swal({
          type: 'warning',
          title: 'Oops!',
          text: 'Please fill your new password'
        })
        return false
      }
      if (this.password.repeatPassword === '') {
        this.$swal({
          type: 'warning',
          title: 'Oops!',
          text: 'Please repeat your new password'
        })
        return false
      }
      if (this.password.repeatPassword !== this.password.newPassword) {
        this.$swal({
          type: 'warning',
          title: 'Oops!',
          text: 'New password and repeat password are not the same'
        })
        return false
      }

      usersAPI.changePassword(this.password)
        .then(res => {
          this.$swal({
            type: 'success',
            title: 'OK'
          })
        }, err => {
          this.$swal({
            type: 'warning',
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
        avatar: ''
      },
      password: {
        oldPassword: '',
        newPassword: '',
        repeatPassword: ''
      },
      previewAvatar: false,
      avatarImage: '',
      oldAvatarImage: '',
      avatarFileError: false
    }
  }
}
</script>

<style lang="scss" scoped>
.user-avatar {
  width: 100px;
  height: 100px;
  background: {
    position: 50% 50%;
    repeat: no-repeat;
    color: #666;
    size: cover;
  }
  border-radius: 50%;
  margin: 1rem auto;
}
</style>


import Vue from 'vue'
import Router from 'vue-router'
import Homepage from '@/components/Homepage'
import Entry from '@/components/Entry'
import Register from '@/components/Register'
import Login from '@/components/Login'
import Logout from '@/components/Logout'

import Owner from '@/components/Owner'
import Entries from '@/components/my/Entries'
import Settings from '@/components/my/Settings'

Vue.use(Router)

export default new Router({
  mode: 'history',
  scrollBehavior: (to, from, savedPosition) => {
    if (savedPosition) {
      return savedPosition
    } else if (to.hash) {
      return { selector: to.hash }
    } else {
      return { x: 0, y: 0 }
    }
  },
  routes: [
    {
      path: '/logout',
      name: 'Logout',
      component: Logout
    },
    {
      path: '/login',
      redirect: '/signin'
    },
    {
      path: '/signin',
      name: 'Login',
      component: Login
    },
    {
      path: '/register',
      redirect: '/signup'
    },
    {
      path: '/signup',
      name: 'Register',
      component: Register
    },
    {
      path: '/my',
      component: Owner,
      children: [
        {
          path: 'entries/:postId?',
          name: 'Entries',
          component: Entries,
          props: true
        },
        {
          path: 'settings',
          name: 'Settings',
          component: Settings
        }
      ]
    },
    {
      path: '/entry/:alias',
      name: 'Entry',
      component: Entry
    },
    {
      path: '/',
      name: 'Homepage',
      component: Homepage
    }
  ]
})

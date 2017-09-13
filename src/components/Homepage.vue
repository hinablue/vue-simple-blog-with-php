<template lang="pug">
  b-row.homepage
    b-col.header(cols="12")
      router-link(tag="h1", :to="{ name: 'Homepage' }") My Stories
    b-col.section(cols="12")
    b-card-group(columns)
      b-card(v-for="post in posts",
        :key="post.id",
        :img-src="post.cover",
        :img-alt="post.title",
        img-top,
        no-body)
          router-link.pointer(tag="h4",
            slot="header",
            :to="{ name: 'Entry', params: { alias: post.alias }}") {{ post.title }}
          b-card-body
            p.card-text(v-html="truncate(post.text)")
            small.text-muted {{ toNow(post.updated_at) }}
          b-card-footer
            .user-avatar.mr-2(:style="avatarStyles(post.author.avatar)")
            small {{ post.author.name }}
      infinite-loading(ref="infiniteLoading", spinner="circles", :on-infinite="onInfinite")
        span(slot="no-results")
</template>

<script>
import postsAPI from '@/api/posts'
import { distanceInWordsToNow, parse } from 'date-fns'
import {
  bRow,
  bCard,
  bCardBody,
  bCardFooter,
  bCardImg,
  bCardGroup,
  bImg
} from 'bootstrap-vue/lib/components'

import InfiniteLoading from 'vue-infinite-loading'

export default {
  name: 'homepage',
  components: {
    bRow: bRow,
    bCard: bCard,
    bCardBody: bCardBody,
    bCardFooter: bCardFooter,
    bCardImg: bCardImg,
    bCardGroup: bCardGroup,
    bImg: bImg,
    infiniteLoading: InfiniteLoading
  },
  beforeRouteEnter (to, from, next) {
    postsAPI.fetchPosts()
      .then(res => {
        next(vm => {
          vm.posts = res.results.items
          vm.totalPages = res.results.totalPages
        })
      }, () => {
        next(vm => {
          vm.postsIsEmpty = true
        })
      })
  },
  methods: {
    truncate (string) {
      return string.substring(0, 120) + '...'
    },
    onInfinite () {
      if (this.currentPage > 0 &&
        this.currentPage + 1 <= this.totalPages
      ) {
        this.currentPage++
        postsAPI.fetchPosts({
          page: this.currentPage
        }).then(res => {
          this.posts = this.posts.concat(res.results.items)
          this.$refs.infiniteLoading.$emit('$InfiniteLoading:complete')
        })
      } else {
        this.$refs.infiniteLoading.$emit('$InfiniteLoading:complete')
      }
    },
    avatarStyles (avatar) {
      return {
        backgroundImage: 'url(' + avatar + ')'
      }
    },
    toNow (date) {
      return distanceInWordsToNow(parse(date), { addSuffix: true })
    }
  },
  data () {
    return {
      postsIsEmpty: false,
      posts: [],
      currentPage: 1,
      totalPages: 0
    }
  }
}
</script>

<style lang="scss" scoped>
.user-avatar {
  display: inline-block;
  width: 30px;
  height: 30px;
  background: {
    position: 50% 50%;
    repeat: no-repeat;
    color: #666;
    size: cover;
  }
  border-radius: 50%;
  vertical-align: middle;
}
</style>


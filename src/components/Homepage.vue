<template lang="pug">
  b-container(fluid)
    b-row.homepage
      b-col.header(cols="12")
        router-link.my-4(tag="h1", :to="{ name: 'Homepage' }") My Stories
      b-col.section.mb-4(
        cols="12",
        md="6",
        lg="4",
        v-for="post in posts",
        :key="post.id")
        b-card(:img-src="post.cover",
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
      b-col(cols="12")
        infinite-loading(ref="infiniteLoading", spinner="circles", @infinite="onInfinite")
          span(slot="no-results")
</template>

<script>
import postsAPI from '@/api/posts'
import { distanceInWordsToNow, parse } from 'date-fns'
import InfiniteLoading from 'vue-infinite-loading'

export default {
  name: 'homepage',
  components: {
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
      if (String(string).length > 300) {
        return string.substring(0, 300) + '...'
      }
      return string
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


<template lang="pug">
  .wrapper.px-3
    b-row.entries
      b-col(cols="3")
        b-row.mt-4(align-v="center")
          b-col(cols="8")
            h3 My Stories
          b-col.text-right(cols="4")
            router-link.pointer.new-story-badge.mt-0.mb-2(tag="div", :to="{ name: 'Entries' }")
              b-badge(pill, variant="info")
                i.mdi.mdi-lead-pencil
        hr
        b-list-group.entries-list
          b-list-group-item(:to="{ name: 'Entries', params: { postId: post.id }}",
            v-for="post in posts.items",
            :key="post.id",
            variant="info")
            b-media.mb-2(right-align, vertical-align="center")
              h5.mb-0 {{ post.title }}
              small {{ toNow(post.updated_at) }}
          infinite-loading(ref="infiniteLoading", spinner="circles", @infinite="onInfinite")
            span(slot="no-results")
      b-col(cols="9")
        b-row.mt-3(align-v="center")
          b-col(cols="10")
            b-form-group.story-title-group(label-for="story-title")
              b-form-input(id="story-title",
                type="text",
                size="lg",
                v-model.trim="story.title",
                required,
                placeholder="Your story title")
          b-col.text-right(cols="2")
            b-dropdown#save.m-md-2(:text="storyStatus", size="sm", variant="info")
              b-dropdown-item(@click.prevent="saveStory('draft')") Save to draft
              b-dropdown-item(@click.prevent="saveStory('publish')") Publish
        b-col(cols="12")
          mavon-editor.markdown-editor(
            ref="editor",
            :toolbars="toolbars",
            language="en",
            placeholder="Start your story...",
            :value="story.markdown",
            @change="updateStory",
            @imgAdd="imageUploader")
</template>

<script>
import postsAPI from '@/api/posts'
import uploaderAPI from '@/api/uploader'
import { distanceInWordsToNow, parse } from 'date-fns'

import 'mavon-editor/dist/css/index.css'
import { mavonEditor } from 'mavon-editor'

import InfiniteLoading from 'vue-infinite-loading'

export default {
  name: 'entries',
  components: {
    infiniteLoading: InfiniteLoading,
    mavonEditor: mavonEditor
  },
  props: {
    postId: {
      type: String,
      default: ''
    }
  },
  beforeRouteEnter (to, from, next) {
    postsAPI.fetchMyPosts()
      .then(res => {
        next(vm => {
          vm.posts = res.results
          if (vm.postId !== '') {
            let post = vm.posts.items.find(post => post.id === vm.postId)
            if (typeof post !== 'undefined') {
              vm.story.id = vm.postId
              vm.story.title = post.title
              vm.story.markdown = post.markdown
              vm.story.status = post.status === 'published'
            }
          }
        })
      }, () => {
        next(vm => {
          vm.postsIsEmpty = true
        })
      })
  },
  computed: {
    storyStatus () {
      return this.story.status ? 'UPDATE' : 'SAVE'
    }
  },
  methods: {
    toNow (date) {
      return distanceInWordsToNow(parse(date), { addSuffix: true })
    },
    updateStory (story, rendered) {
      this.story.markdown = story
      this.story.html = rendered
    },
    addStoriesList (story) {
      this.posts.items.unshift({
        id: story.id,
        title: story.title,
        markdown: story.markdown,
        html: story.html,
        updated_at: new Date(),
        created_at: new Date(),
        status: story.published ? 'published' : 'draft'
      })
    },
    updateStoriesList (story) {
      let post = this.posts.items.find(post => post.id === story.id)
      if (typeof post !== 'undefined') {
        post.title = story.title
        post.updated_at = new Date()
      }
    },
    onInfinite () {
      if (this.currentPage > 0 &&
        this.currentPage + 1 <= this.posts.totalPages
      ) {
        this.currentPage++
        postsAPI.fetchMyPosts({
          page: this.currentPage
        })
        .then(res => {
          this.posts.items = this.posts.items.concat(res.results.items)
          this.$refs.infiniteLoading.$emit('$InfiniteLoading:loaded')
        }, () => {
          this.$refs.infiniteLoading.$emit('$InfiniteLoading:complete')
        })
      } else {
        this.$refs.infiniteLoading.$emit('$InfiniteLoading:complete')
      }
    },
    saveStory (type) {
      if (this.story.id !== '') {
        postsAPI.updatePost({
          id: this.story.id,
          title: this.story.title,
          markdown: this.story.markdown,
          html: this.story.html,
          published: type === 'publish'
        }).then(res => {
          this.updateStoriesList({
            id: this.story.id,
            title: this.story.title,
            markdown: this.story.markdown,
            html: this.story.html,
            published: type === 'publish'
          })
          this.$swal({
            type: 'success',
            title: 'OK'
          })
        }, err => {
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: err.messages
          })
        })
      } else {
        if (String(this.story.title).length === 0) {
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: 'Please fill your story title'
          })
          return false
        }

        postsAPI.addPost({
          title: this.story.title,
          markdown: this.story.markdown,
          html: this.story.html,
          published: type === 'publish'
        }).then(res => {
          this.addStoriesList({
            id: res.results,
            title: this.story.title,
            markdown: this.story.markdown,
            html: this.story.html,
            published: type === 'publish'
          })
          this.$swal({
            type: 'success',
            title: 'OK'
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
    imageUploader (filename, file) {
      if (file.size > 5 * 1024 * 1024 || file.size === 0) {
        return false
      }

      let data = new FormData()
      data.append('isPost', true)
      data.append('file', file)
      uploaderAPI.upload(data)
        .then(res => {
          this.$refs.editor.$imgUpdateByUrl(filename, res.results.url)
          this.$refs.editor.$img2Url(filename, res.results.url)
        }, err => {
          this.$swal({
            type: 'error',
            title: 'Oops!',
            text: err.messages
          })
        })
    }
  },
  mounted () {
    console.log(this.$refs.editor)
  },
  watch: {
    postId (id) {
      let post = this.posts.items.find(post => post.id === this.postId)
      if (typeof post !== 'undefined') {
        this.story.id = this.postId
        this.story.title = post.title
        this.story.markdown = post.markdown
        this.story.status = post.status === 'published'
      } else {
        this.story.id = ''
        this.story.title = ''
        this.story.markdown = ''
        this.story.html = ''
        this.story.status = false
      }
    }
  },
  data () {
    return {
      currentPage: 1,
      postsIsEmpty: false,
      posts: {
        items: [],
        totalPages: 0,
        totalItems: 0
      },
      story: {
        id: '',
        title: '',
        markdown: '',
        html: '',
        status: false
      },
      toolbars: {
        bold: true, // 粗体
        italic: true, // 斜体
        header: false, // 标题
        underline: true, // 下划线
        strikethrough: true, // 中划线
        mark: false, // 标记
        superscript: false, // 上角标
        subscript: false, // 下角标
        quote: true, // 引用
        ol: true, // 有序列表
        ul: true, // 无序列表
        link: true, // 链接
        imagelink: true, // 图片链接
        code: true, // code
        table: true, // 表格
        fullscreen: true, // 全屏编辑
        readmodel: true, // 沉浸式阅读
        htmlcode: false, // 展示html源码
        help: true, // 帮助
        /* 1.3.5 */
        undo: false, // 上一步
        redo: false, // 下一步
        trash: false, // 清空
        save: false, // 保存（触发events中的save事件）
        /* 1.4.2 */
        navigation: false, // 导航目录
        /* 2.1.8 */
        alignleft: false, // 左对齐
        aligncenter: false, // 居中
        alignright: false, // 右对齐
        /* 2.2.1 */
        subfield: true, // 单双栏模式
        preview: true // 预览
      }
    }
  }
}
</script>

<style lang="scss" scoped>
#story-title {
  border: 0 none;
  font-size: 2.15rem;
}
.entries {
  height: 100vh;
  overflow: hidden;
  .new-story-badge {
    display: inline-block;
    span.badge {
      font-size: 1.125rem;
    }
  }
  .story-title-group {
    margin-bottom: 0;
  }
  .markdown-editor {
    height: 90vh;
    z-index: 10;
  }
  .entries-list {
    overflow-x: hidden;
    overflow-y: auto;
    height: 90vh;
  }
}

// Overwrite.
.list-group-item-info {
  background: #fff;
}
</style>


<template>
  <div>
    <el-dialog :title="title" v-model="visible" width="896px" :before-close="handleClose" :modal="false">
      <upload-index v-if="visible" @getImage="getImage" />
    </el-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
export default {
  name: 'UploadFroms',
  components: { UploadIndex: defineAsyncComponent(() => import('@/components/uploadPicture/index.vue')) },
  data() {
    return {
      title: "图片管理",
      visible: false,
      callback: function () {}
    }
  },

  methods: {
    handleClose() {
      this.visible = false
    },
    getImage(img) {
      this.callback(img)
      this.visible = false
    }
  }
}
</script>

<style scoped></style>

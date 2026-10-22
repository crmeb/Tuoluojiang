<template>
  <section class="app-main">
    <transition name="el-fade-in-linear">
      <router-view :key="key" />
    </transition>
  </section>
</template>

<script>

export default {
  name: 'AppMain',

  computed: {
    key() {
      return this.$route.path
    }
  }
}
</script>

<style lang="scss" scoped>
.app-main {
  /* 50= navbar  50  */
  // min-height: calc(100vh - 66px);
  width: 100%;
  position: relative;
  flex: 1;
  min-height: 0;
}

.fixed-header + .app-main {
  padding-top: 60px;
}
</style>

<style lang="scss" scoped>
// fix css style bug in open el-dialog
.el-popup-parent--hidden {
  .fixed-header {
    padding-right: 15px;
  }
}
</style>

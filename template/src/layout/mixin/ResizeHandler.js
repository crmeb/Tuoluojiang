import { mapActions } from 'pinia'
import { useAppStore } from '@/stores/app'

const WIDTH = 992 // refer to Bootstrap's responsive design

export default {
  watch: {
    $route(route) {
      if (this.device === 'mobile' && this.sidebar.opened) {
        this.closeSideBar(false)
      }
    }
  },
  beforeMount() {
    window.addEventListener('resize', this.$_resizeHandler)
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.$_resizeHandler)
  },
  mounted() {
    const isMobile = this.$_isMobile()
    if (isMobile) {
      this.toggleDevice('mobile')
      this.closeSideBar(true)
    }
  },
  methods: {
    ...mapActions(useAppStore, ['toggleDevice', 'closeSideBar']),
    // use $_ for mixins properties
    // https://vuejs.org/v2/style-guide/index.html#Private-property-names-essential
    $_isMobile() {
      const rect = document.body.getBoundingClientRect()
      return rect.width - 1 < WIDTH
    },
    $_resizeHandler() {
      if (!document.hidden) {
        const isMobile = this.$_isMobile()
        this.toggleDevice(isMobile ? 'mobile' : 'desktop')

        if (isMobile) {
          this.closeSideBar(true)
        }
      }
    }
  }
}

import { defineComponent } from 'vue'
import { mapState } from 'pinia'
import { useAppStore } from '@/stores/app'

/** el-sub-menu 实例上被改写的内部方法 */
type SubMenuInstance = { handleMouseleave: (e: Event) => void }

export default defineComponent({
  computed: {
    ...mapState(useAppStore, ['device'])
  },
  mounted() {
    // In order to fix the click on menu on the ios device will trigger the mouseleave bug
    // https://github.com/PanJiaChen/vue-element-admin/issues/1135
    this.fixBugIniOS()
  },
  methods: {
    fixBugIniOS() {
      const $subMenu = this.$refs.subMenu as SubMenuInstance | undefined
      if ($subMenu) {
        const handleMouseleave = $subMenu.handleMouseleave
        $subMenu.handleMouseleave = (e: Event) => {
          if (this.device === 'mobile') {
            return
          }
          handleMouseleave(e)
        }
      }
    }
  }
})

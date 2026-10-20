import { SETTING_PANEL_HUB } from '@/utils/eventHub'

export default {
  inject: {
    settingPanelHub: { from: SETTING_PANEL_HUB, default: null }
  },
  created() {},
  methods: {
    editEventHandler(eventName, eventParams) {
      this.settingPanelHub?.emit('editEventHandler', eventName, [...eventParams])
    },

  }
}

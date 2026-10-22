import defaultSetting from "@/settings";

/**
 * 动态加载Luckysheet资源的工具函数
 */

// 跟踪加载状态，避免重复加载
let luckysheetLoaded = false
let loadingPromise = null

/**
 * 动态加载Luckysheet资源
 * @returns {Promise<void>} 加载完成的Promise
 */
export function loadLuckysheet() {
  // 如果已经加载，直接返回Promise.resolve
  if (luckysheetLoaded) {
    return Promise.resolve()
  }

  // 如果正在加载，返回现有的Promise
  if (loadingPromise) {
    return loadingPromise
  }

  // 开始加载过程
  loadingPromise = new Promise((resolve, reject) => {
    // 检查是否已存在样式标签，避免重复加载
    const checkAndLoadCSS = (href) => {
      const existingLink = document.querySelector(`link[href="${href}"]`)
      if (!existingLink) {
        return new Promise((resolveCSS, rejectCSS) => {
          const link = document.createElement('link')
          link.rel = 'stylesheet'
          link.href = href
          link.onload = () => resolveCSS()
          link.onerror = () => rejectCSS(new Error(`Failed to load CSS: ${href}`))
          document.head.appendChild(link)
        })
      }
      return Promise.resolve()
    }

    // 检查并加载脚本
    const checkAndLoadScript = (src) => {
      const existingScript = document.querySelector(`script[src="${src}"]`)
      if (!existingScript) {
        return new Promise((resolveScript, rejectScript) => {
          const script = document.createElement('script')
          script.src = src
          script.onload = () => resolveScript()
          script.onerror = () => rejectScript(new Error(`Failed to load script: ${src}`))
          document.head.appendChild(script)
        })
      }
      return Promise.resolve()
    }

    const prefix = defaultSetting.roterPre;

    // 定义要加载的资源
    const resources = [
      // CSS资源
      { type: 'css', src: prefix + '/luckysheet/assets/iconfont/iconfont.css' },
      { type: 'css', src: prefix + '/luckysheet/plugins/css/pluginsCss.css' },
      { type: 'css', src: prefix + '/luckysheet/plugins/plugins.css' },
      { type: 'css', src: prefix + '/luckysheet/css/luckysheet.css' },
      // JS资源
      { type: 'js', src: prefix + '/luckysheet/plugins/js/plugin.js' },
      { type: 'js', src: prefix + '/luckysheet/luckysheet.umd.js' }
    ]

    // 顺序加载所有资源
    const loadResource = (index) => {
      if (index >= resources.length) {
        // 所有资源加载完成
        luckysheetLoaded = true
        loadingPromise = null
        resolve()
        return
      }
      const resource =  resources[index]
      let loadPromise

      if (resource.type === 'css') {
        loadPromise = checkAndLoadCSS(resource.src)
      } else {
        loadPromise = checkAndLoadScript(resource.src)
      }

      loadPromise
        .then(() => {
          loadResource(index + 1)
        })
        .catch(error => {
          loadingPromise = null
          reject(error)
        })
    }

    loadResource(0)
  })

  return loadingPromise
}

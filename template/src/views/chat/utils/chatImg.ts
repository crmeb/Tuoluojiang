// chat 模块的供应商 / 模型 logo 都放在 assets/chat 下，接口只返回文件名（如 deepseek.png），
// 这里统一做一次文件名 -> 打包后地址的映射，避免各组件重复写 glob
const chatImgContext = import.meta.glob<string>('../../../assets/chat/*.{png,jpg,svg}', {
  eager: true,
  query: '?url',
  import: 'default',
})

const chatImgModules = Object.keys(chatImgContext).reduce<Record<string, string>>((modules, key) => {
  modules[key.slice(key.lastIndexOf('/') + 1)] = chatImgContext[key]
  return modules
}, {})

// 模型未携带 logo 时的兜底图
export const DEFAULT_MODEL_IMG = chatImgModules['deepseek.png'] || ''

/**
 * 取 chat 模块图片地址
 * @param {string} pic 图片文件名，如 deepseek.png
 * @returns {string} 打包后的图片地址，找不到时返回空串
 */
export function getChatImg(pic: string) {
  return chatImgModules[pic] || ''
}

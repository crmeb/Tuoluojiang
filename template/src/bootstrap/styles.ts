import 'normalize.css/normalize.css'
// element-ui 图标字体，须在 Element Plus 样式之前：两者的 .el-icon--left/right 等同权重规则以后者为准
import '@/assets/element-icons/icon.css'
// Keep this before project styles so Element Plus theme is overridden consistently.
import '@/styles/element-variables.scss'
import '@/styles/index.scss'
import '@/icons/designer-icon.css'
// 下面两份共用 "iconfont" 这一个 font-family。同名 @font-face 会合并成同族的多个候选
// face，浏览器逐字符地从后往前挑第一个含该字形的 face——所以引入顺序即优先级，越靠下
// 越优先。两份分属不同 iconfont 项目（1570615 / 5121724），码位基本不重叠，靠这套
// 回退机制共存。再往这个族里加第三份前，务必先查码位是否与它们冲突：撞车不会报错，
// 只会静默画出另一个图标。新增的独立图标集请像 designer-icon 那样用独立族名。
import '@/assets/iconfont/iconfont.css'
import '@/assets/iconfont/iconfont-crmeb.css'

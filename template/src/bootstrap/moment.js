// moment 是模块单例：语言包在启动时设置一次即可，
// 业务代码直接 `import moment from "moment"` 拿到的就是已切到中文的实例。
import moment from "moment";
import "moment/locale/zh-cn";

moment.locale("zh-cn");

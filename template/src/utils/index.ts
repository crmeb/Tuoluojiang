/**
 * Created by PanJiaChen on 16/11/18.
 */

/**
 * Parse the time to string
 * @param {(Object|string|number)} time
 * @param {string} cFormat
 * @returns {string | null}
 */
export function parseTime(time?: Date | string | number, cFormat?: string): string | null {
  if (arguments.length === 0) {
    return null;
  }
  const format = cFormat || '{y}-{m}-{d} {h}:{i}:{s}';
  let date: Date;
  if (typeof time === 'object') {
    date = time;
  } else {
    if (typeof time === 'string') {
      if (/^[0-9]+$/.test(time)) {
        // support "1548221490638"
        time = parseInt(time);
      } else {
        // support safari
        // https://stackoverflow.com/questions/4310953/invalid-date-in-safari
        time = time.replace(new RegExp(/-/gm), '/');
      }
    }

    if (typeof time === 'number' && time.toString().length === 10) {
      time = time * 1000;
    }
    date = new Date(time as string | number);
  }
  const formatObj: Record<string, number> = {
    y: date.getFullYear(),
    m: date.getMonth() + 1,
    d: date.getDate(),
    h: date.getHours(),
    i: date.getMinutes(),
    s: date.getSeconds(),
    a: date.getDay(),
  };
  const time_str = format.replace(/{([ymdhisa])+}/g, (result, key) => {
    const value = formatObj[key];
    // Note: getDay() returns 0 on Sunday
    if (key === 'a') {
      return ['日', '一', '二', '三', '四', '五', '六'][value];
    }
    return value.toString().padStart(2, '0');
  });
  return time_str;
}

/**
 * @param {number} time
 * @param {string} option
 * @returns {string}
 */
export function formatTime(time: number | string, option?: string) {
  if (('' + time).length === 10) {
    time = parseInt(String(time)) * 1000;
  } else {
    time = +time;
  }
  const d = new Date(time);
  const now = Date.now();

  const diff = (now - d.getTime()) / 1000;

  if (diff < 30) {
    return '刚刚';
  } else if (diff < 3600) {
    // less 1 hour
    return Math.ceil(diff / 60) + '分钟前';
  } else if (diff < 3600 * 24) {
    return Math.ceil(diff / 3600) + '小时前';
  } else if (diff < 3600 * 24 * 2) {
    return '1天前';
  }
  if (option) {
    return parseTime(time, option);
  } else {
    return d.getMonth() + 1 + '月' + d.getDate() + '日' + d.getHours() + '时' + d.getMinutes() + '分';
  }
}


/**
 * @param {string} input value
 * @returns {number} output value
 */
export function byteLength(str: string) {
  // returns the byte length of an utf8 string
  let s = str.length;
  for (var i = str.length - 1; i >= 0; i--) {
    const code = str.charCodeAt(i);
    if (code > 0x7f && code <= 0x7ff) s++;
    else if (code > 0x7ff && code <= 0xffff) s += 2;
    if (code >= 0xdc00 && code <= 0xdfff) i--;
  }
  return s;
}

/**
 * @param {Array} actual
 * @returns {Array}
 */
function cleanArray<T>(actual: T[]) {
  const newArray: T[] = [];
  for (let i = 0; i < actual.length; i++) {
    if (actual[i]) {
      newArray.push(actual[i]);
    }
  }
  return newArray;
}

/**
 * @param {Object} json
 * @returns {Array}
 */
export function param(json?: Record<string, string | number | boolean | undefined> | null) {
  if (!json) return '';
  return cleanArray(
    Object.keys(json).map((key) => {
      if (json[key] === undefined) return '';
      return encodeURIComponent(key) + '=' + encodeURIComponent(json[key]!);
    }),
  ).join('&');
}


/**
 * @param {string} type
 * @returns {Date}
 */
export function getTime(type: string) {
  if (type === 'start') {
    return new Date().getTime() - 3600 * 1000 * 24 * 90;
  } else {
    return new Date(new Date().toDateString());
  }
}

/**
 * 简易防抖函数
 * @param {Function} func
 * @param {number} wait
 * @param {boolean} immediate
 * @return {*}
 */
export function debounce<F extends (...args: any[]) => any>(func: F, wait: number, immediate?: boolean) {
  let timeout: ReturnType<typeof setTimeout> | null = null,
    args: Parameters<F> | null = null,
    context: unknown,
    timestamp = 0,
    result: ReturnType<F> | undefined;

  const later = function () {
    // 据上一次触发时间间隔
    const last = +new Date() - timestamp;

    // 上次被包装函数被调用时间间隔 last 小于设定时间间隔 wait
    if (last < wait && last > 0) {
      timeout = setTimeout(later, wait - last);
    } else {
      timeout = null;
      // 如果设定为immediate===true，因为开始边界已经调用过了此处无需调用
      if (!immediate) {
        result = func.apply(context, args!);
        if (!timeout) context = args = null;
      }
    }
  };

  return function (this: unknown, ...callArgs: Parameters<F>) {
    context = this;
    args = callArgs;
    timestamp = +new Date();
    const callNow = immediate && !timeout;
    // 如果延时不存在，重新设定延时
    if (!timeout) timeout = setTimeout(later, wait);
    if (callNow) {
      result = func.apply(context, args);
      context = args = null;
    }

    return result;
  };
}

/**
 * This is just a simple version of deep copy
 * Has a lot of edge cases bug
 * If you want to use a perfect deep copy, use lodash's _.cloneDeep
 * @param {Object} source
 * @returns {Object}
 */
export function deepClone<T>(source: T): T {
  if (!source || typeof source !== 'object') {
    throw new Error('error arguments');
  }
  const src = source as Record<string, unknown>;
  const targetObj: Record<string, unknown> = source.constructor === Array ? ([] as unknown as Record<string, unknown>) : {};
  Object.keys(src).forEach((keys) => {
    if (src[keys] && typeof src[keys] === 'object') {
      targetObj[keys] = deepClone(src[keys]);
    } else {
      targetObj[keys] = src[keys];
    }
  });
  return targetObj as T;
}

/**
 * @param {Array} arr
 * @returns {Array}
 */
export function uniqueArr<T>(arr: Iterable<T>) {
  return Array.from(new Set(arr));
}








/**
 * 判断两个日期相差多少小时
 */
export function divTime(time1: string | number | Date, time2: string | number | Date, type?: string) {
  const t1 = Date.parse(String(new Date(time1)));
  const t2 = Date.parse(String(new Date(time2)));
  if (t1 > t2) return 0;
  // if ( time1 && time2) return 0
  return type === 'day'
    ? Math.abs(Math.trunc((t2 - t1) / (1000 * 60 * 60 * 24)))
    : Math.abs(Math.trunc((t2 - t1) / 1000 / 3600));
}

/**
 * 数字金额转换成大写中文
 */
export function intToChinese(value: number | string) {
  let n = String(value);
  if (!/^(0|[1-9]\d*)(\.\d+)?$/.test(n)) {
    return '未输入'; // 判断数据是否大于0
  }
  var unit = '仟佰拾亿仟佰拾万仟佰拾元角分';
  var str = '';
  n += '00';
  var indexpoint = n.indexOf('.'); // 如果是小数，截取小数点前面的位数
  if (indexpoint >= 0) {
    n = n.substring(0, indexpoint) + n.substr(indexpoint + 1, 2); // 若为小数，截取需要使用的unit单位
  }
  unit = unit.substr(unit.length - n.length); // 若为整数，截取需要使用的unit单位
  for (var i = 0; i < n.length; i++) {
    str += '零壹贰叁肆伍陆柒捌玖'.charAt(Number(n.charAt(i))) + unit.charAt(i); // 遍历转化为大写的数字
  }
  return str
    .replace(/零(仟|佰|拾|角)/g, '零')
    .replace(/(零)+/g, '零')
    .replace(/零(万|亿|元)/g, '$1')
    .replace(/(亿)万|壹(拾)/g, '$1$2')
    .replace(/^元零?|零分/g, '')
    .replace(/元$/g, '元整'); // 替换掉数字里面的零字符，得到结果
}

import SparkMD5 from 'spark-md5'
import request from '@/api/request'

export const uploadByPieces = (file, option = {}, url) => {
  return new Promise((resolve, reject) => {
    // 基础配置
    const config = {
      CHUNK_SIZE: 5 * 1024 * 1024, // 5MB/分片
      MAX_RETRIES: 3,
      RETRY_DELAY: 1000,
      MAX_CONCURRENT: 2, // 并发数建议设为2-3，避免任务争抢
      debug: false, // 强制开启调试日志
    };

    // 核心变量
    let fileMD5 = '';
    let resData={};
    let chunkCount = 0;
    let uploadedChunks = [];
    let needUploadChunks = []; // 待上传分片列表（关键：用数组存储，避免争抢）

    // 调试日志
    const log = (msg) => {
      if (config.debug) {
        console.log(`[分片上传][${new Date().getTime()}] ${msg}`);
      }
    };

    // 1. 计算文件MD5（确保唯一标识正确）
    const calculateMD5 = () => {
      return new Promise((resolve) => {
        const spark = new SparkMD5.ArrayBuffer();
        const reader = new FileReader();
        let offset = 0;

        const loadChunk = () => {
          const blob = file.slice(offset, offset + config.CHUNK_SIZE);
          reader.readAsArrayBuffer(blob);
          reader.onload = (e) => {
            spark.append(e.target.result);
            offset += config.CHUNK_SIZE;
            if (offset < file.size) loadChunk();
            else {
              fileMD5 = spark.end();
              log(`MD5计算完成: ${fileMD5}`);
             
              resolve(fileMD5);
            }
          };
        };
        loadChunk();
      });
    };

    // 2. 初始化待上传分片列表（关键：确保包含所有未上传分片）
    const initNeedUploadChunks = () => {
      // 清空列表，避免残留
      needUploadChunks = [];
      // 遍历所有分片，排除本地已上传的
      for (let i = 0; i < chunkCount; i++) {
        if (!uploadedChunks.includes(i)) {
          needUploadChunks.push(i);
        }
      }
      log(`初始化待上传分片列表: ${needUploadChunks.join(',')}（共${needUploadChunks.length}个）`);
      // 校验：若列表长度与预期不符（如预期3个实际1个），强制重置
      if (needUploadChunks.length !== chunkCount - uploadedChunks.length) {
        log('待上传分片列表长度异常，强制重新生成');
        needUploadChunks = Array.from({ length: chunkCount }, (_, i) => i).filter(i => !uploadedChunks.includes(i));
        log(`重新生成后: ${needUploadChunks.join(',')}`);
      }
    };
    // 3. 获取分片信息（确保生成正确的分片）
    const getChunkInfo = (chunkIndex) => {
      const start = chunkIndex * config.CHUNK_SIZE;
      const end = Math.min(file.size, start + config.CHUNK_SIZE);
      const chunk = file.slice(start, end);
      log(`生成分片 ${chunkIndex}: 范围[${start},${end}]，大小${(end - start) / 1024 / 1024}MB`);
      return { chunk, chunkIndex, start, end };
    };
    // 4. 上传单个分片（确保请求被触发）
    const uploadChunk = (chunkIndex) => {
      return new Promise((resolve, reject) => {
        const { chunk } = getChunkInfo(chunkIndex);
        const formData = new FormData();
        formData.append('file', chunk,file.name);
        formData.append('md5', fileMD5);
        formData.append('chunk_index', chunkIndex);
        formData.append('chunk_total', chunkCount);
        if (Object.keys(option).length > 0) {
          for (let key in option) {
            formData.append(key, option[key])
          }
        }
        request.post(url, formData).then(async (res) => {
          if (res.data.src) {
            resData=res
            resolve(res)
          } else if (res.status === 200) {
            resolve(chunkIndex);
          }else{
            reject(res)
          }
        }).catch((err) => {
          log(`分片 ${chunkIndex} 上传失败: ${err.message}`);
          reject(err);
        });
      });
    };
    // 5. 并发上传控制器（核心修复：确保所有分片被遍历）
    const uploadWithConcurrent = () => {
      return new Promise((resolve, reject) => {
        let completedCount = 0; // 已完成的分片数
        let isRejected = false; // 是否已拒绝

        // 单个并发任务（循环取分片，直到列表为空）
        const runTask = async () => {
          while (needUploadChunks.length > 0 && !isRejected) {
            // 关键：用splice(0,1)取第一个分片，避免多个任务争抢同一分片
            const [chunkIndex] = needUploadChunks.splice(0, 1);
            log(`任务获取分片: ${chunkIndex}，剩余待上传: ${needUploadChunks.length}`);

            let retries = 0;
            while (retries < config.MAX_RETRIES && !isRejected) {
              try {
                await uploadChunk(chunkIndex);
                // 上传成功：记录已上传分片
                uploadedChunks.push(chunkIndex);
                completedCount++;
                log(`分片 ${chunkIndex} 处理完成，已完成总数: ${completedCount}/${chunkCount}`);
                break; // 跳出重试循环
              } catch (err) {
                retries++;
                if (retries >= config.MAX_RETRIES) {
                  isRejected = true;
                  reject(new Error(`分片 ${chunkIndex} 重试${config.MAX_RETRIES}次失败: ${err.message}`));
                  return;
                }
                const delay = config.RETRY_DELAY * Math.pow(2, retries);
                log(`分片 ${chunkIndex} 重试${retries}次，延迟${delay}ms`);
                await new Promise(res => setTimeout(res, delay));
              }
            }
          }
        };

        // 启动并发任务（数量=MAX_CONCURRENT）
        const tasks = Array.from({ length: config.MAX_CONCURRENT }, runTask);
        log(`启动${config.MAX_CONCURRENT}个并发任务`);

        // 等待所有任务完成
        Promise.all(tasks).then(() => {
          if (completedCount === chunkCount) {
            log('所有分片上传完成');
            resolve();
          } else {
            reject(new Error(`部分分片未上传：已完成${completedCount}/${chunkCount}`));
          }
        }).catch((err) => {
          if (!isRejected) {
            isRejected = true;
            reject(err);
          }
        });
      });
    };

    // 主流程
    const init = async () => {
      try {
        log(`开始上传：文件名=${file.name}，大小=${file.size / 1024 / 1024}MB`);
        // 步骤1：计算MD5
        await calculateMD5();
        // 步骤2：计算总分片数（确保正确）
        chunkCount = Math.ceil(file.size / config.CHUNK_SIZE);
        log(`总分片数计算完成：${chunkCount}个（分片大小${config.CHUNK_SIZE / 1024 / 1024}MB）`);
        // 步骤3：初始化待上传分片列表（清空本地缓存，确保从头开始）
        uploadedChunks = [];
        localStorage.removeItem(`upload_${fileMD5}`);
        initNeedUploadChunks();
        // 步骤4：并发上传
        await uploadWithConcurrent();
        // 步骤5：完成
        option.onProgress?.(100);
        resolve(resData);
      } catch (err) {
        log(`上传失败：${err.message}`);
        option.onError?.(err);
        reject(err);
      }
    };

    init();
  });
};

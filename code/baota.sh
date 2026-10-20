#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

php_version='80'
mysql_version='5.7'
redis_version='6.2'
action_type='install'
root_path=$(cat /var/bt_setupPath.conf)
setup_path=$root_path/server

#宝塔是否已安装
if [ -z "$root_path" ]; then
    echo "请先安装宝塔"
    exit 1
fi

#nginx是否已安装
if [ ! -f "${setup_path}/nginx/sbin/nginx" ]; then
    echo "请先安装nginx并配置网站"
    exit 1
fi

#安装php
php_install=1
#php路径变量
php_path="${setup_path}/php/80/bin/php"
# 获取已安装的php版本
for phpVer in 80; do
    if [ -d "${setup_path}/php/${phpVer}/bin" ]; then
        php_version=${phpVer}
        php_install=0
    fi
done
if [ $php_install == 1 ]; then
. ${setup_path}/panel/install/install_soft.sh 1 $action_type php $php_version
fi
case "${php_version}" in
    '80')
        extFile="${setup_path}/php/80/lib/php/extensions/no-debug-non-zts-20200930"
        php_path="${setup_path}/php/80/bin/php"
    ;;
    '81')
        extFile="${setup_path}/php/81/lib/php/extensions/no-debug-non-zts-20210902"
        php_path="${setup_path}/php/81/bin/php"
    ;;
esac

echo "PHP $php_version 安装成功"
echo '---------------------------------'

# 检查Composer是否在系统路径中可执行
if command -v composer >/dev/null 2>&1; then
    echo "Composer已安装。"
else
    echo "未找到Composer安装，正在执行安装操作..."

    # 以下是安装Composer的步骤

    # 下载Composer安装脚本
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

    # 执行安装脚本
    php composer-setup.php

    # 将Composer可执行文件移动到系统路径下，以便在任何地方都能调用
    mv composer.phar /usr/local/bin/composer

    # 删除安装脚本，因为已经不需要了
    rm composer-setup.php

    echo "Composer安装完成。"
fi
echo '---------------------------------'

#composer更新
composer install
echo "composer 依赖包更新成功"
echo '---------------------------------'

#安装mysql
if [ ! -d "${setup_path}/mysql" ]; then
. ${setup_path}/panel/install/install_soft.sh 1 $action_type mysql $mysql_version
fi

echo "mysql $mysql_version 安装成功"
echo '---------------------------------'

#安装redis
if [ ! -d "${setup_path}/redis" ]; then
. ${setup_path}/panel/install/install_soft.sh 0 $action_type redis $redis_version
fi

echo "redis $redis_version 安装成功"
echo '---------------------------------'

#安装php-swoole 插件
 if [ ! -e "${extFile}/swoole.so" ]; then
 . ${setup_path}/panel/install/install_soft.sh 1 $action_type swoole5 $php_version
 fi

#禁用函数删除
sed -i 's/,proc_open//' ${setup_path}/php/$php_version/etc/php.ini
sed -i 's/,shell_exec//' ${setup_path}/php/$php_version/etc/php.ini
sed -i 's/,popen//' ${setup_path}/php/$php_version/etc/php.ini
sed -i 's/,putenv//' ${setup_path}/php/$php_version/etc/php.ini
sed -i 's/,exec//' ${setup_path}/php/$php_version/etc/php.ini

#安装php-fileinfo 插件
if [ ! -e "${extFile}/fileinfo.so" ]; then
. ${setup_path}/panel/install/install_soft.sh 1 $action_type fileinfo $php_version
fi

echo 'php-fileinfo 插件安装成功'
echo '---------------------------------'

# 开源版已去除加密运行时，无需安装 swoole_loader
echo '跳过 swoole-loader（开源明文，无需加密扩展）'
echo '---------------------------------'

echo '修改mysql sql_mode配置'
echo '---------------------------------'
#修改mysql配置
# MySQL配置文件路径
CONFIG_FILE="/etc/my.cnf"

# 检查配置文件是否存在
if [ ! -f "$CONFIG_FILE" ]; then
    echo "MySQL配置文件 $CONFIG_FILE 不存在，请检查路径"
    exit 1
fi
# 首先检查是否存在 [mysqld] 段落
if ! grep -q "\[mysqld\]" "$CONFIG_FILE"; then
    echo "\[mysqld\]" >> "$CONFIG_FILE"
fi

# 备份原配置文件（可选）
cp "$CONFIG_FILE" "${CONFIG_FILE}.bak"
echo "MySQL配置文件已备份至 ${CONFIG_FILE}.bak"

# 使用grep检查sql_mode是否存在，如果存在，则替换其值
if grep -q "^[[:space:]]*sql_mode[[:space:]]*=" "$CONFIG_FILE"; then
    # 如果存在，修改 sql_mode 的值
    sed -i 's/^ *sql_mode *=.*$/sql_mode=NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION/' "$CONFIG_FILE"
fi

# 使用grep检查sql-mode是否存在
if grep -q "^[[:space:]]*sql-mode[[:space:]]*=" "$CONFIG_FILE"; then
    # 如果存在，修改 sql-mode 的值
    sed -i 's/^ *sql-mode *=.*$/sql-mode=NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION/' "$CONFIG_FILE"
fi
# 使用grep -E支持扩展正则表达式，同时匹配sql_mode和sql-mode，如果行不存在，则在[mysqld]段末尾添加新的sql_mode设置
if ! grep -qE "^[[:space:]]*(sql_mode|sql-mode)[[:space:]]*=" "$CONFIG_FILE"; then
    sed -i '/\[mysqld\]/a\sql_mode=NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' "$CONFIG_FILE"
fi


# 检查并显示修改后的配置文件中关于sql_mode的部分
grep 'sql-mode' "$CONFIG_FILE"
grep 'sql_mode' "$CONFIG_FILE"

# 重启mysql
echo "MySQL配置文件已更新，正在重载MySQL配置..."
/etc/init.d/mysqld reload
echo "如果没生效请重启MySQL服务。"
echo "--------------------------------------"

#修改nginx配置
project_path=$(cd `dirname $0`; pwd)
project_name="${project_path##*/}"
domain="${project_name//_/.}"
if [ -e "${setup_path}/panel/vhost/nginx/${domain}.conf" ]; then
echo -e "
server
{
    listen 80;
    server_name ${domain};
    index index.php index.html index.htm default.php default.htm default.html;
    root /www/wwwroot/${project_name}/public;

    #SSL-START SSL相关配置，请勿删除或修改下一行带注释的404规则
    #error_page 404/404.html;
    #SSL-END

    #ERROR-PAGE-START  错误页配置，可以注释、删除或修改
    #error_page 404 /404.html;
    #error_page 502 /502.html;
    #ERROR-PAGE-END

    location ~ /purge(/.*) {
        proxy_cache_purge cache_one \$host\$1\$is_args\$args;
    }

    #PROXY-START/
    location  ~* \.(php|jsp|cgi|asp|aspx)$
    {
      proxy_pass http://127.0.0.1:20200;
      proxy_set_header Host \$host;
      proxy_set_header X-Real-IP \$remote_addr;
      proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
      proxy_set_header REMOTE-HOST \$remote_addr;
    }

    location /
    {
      proxy_pass http://127.0.0.1:20200;
      proxy_http_version 1.1;
      proxy_read_timeout 360s;
      proxy_redirect off;
      proxy_set_header Upgrade \$http_upgrade;
      proxy_set_header Connection "upgrade";
      proxy_set_header Host \$host;
      proxy_set_header X-Real-IP \$remote_addr;
      proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
      proxy_set_header REMOTE-HOST \$remote_addr;

      add_header X-Cache \$upstream_cache_status;

      #Set Nginx Cache

         add_header Cache-Control no-cache;
      expires 12h;
    }
    #PROXY-END/

    #禁止访问的文件或目录
    location ~ ^/(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
    {
        return 404;
    }

    #一键申请SSL证书验证目录相关设置
    location ~ \.well-known{
        allow all;
    }

    access_log  /www/wwwlogs/${domain}.log;
    error_log  /www/wwwlogs/${domain}.error.log;
}
" > ${setup_path}/panel/vhost/nginx/${domain}.conf
fi
echo 'nginx配置成功'
echo '---------------------------------'

echo '重载nginx配置'
/etc/init.d/nginx reload
echo '---------------------------------'
echo '系统环境安装成功！'
echo '==============================================='
# 设置目录权限
echo '---------------------------------'
echo "设置目录权限"
cp .env.example .env
chmod -R 755 storage
chmod -R 755 .version
chmod -R 755 .env
[ -f .constant ] && chmod -R 755 .constant
chmod -R 755 public

echo '正在启动系统'
# 启动系统
echo '---------------------------------'
echo "启动系统:$php_path bin/laravels start -d"
$php_path bin/laravels start -d

# 操作说明，进入程序根目录运行 /bin/bash baota.sh

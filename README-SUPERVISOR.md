#**SUPERVISOR**
## 参考文章
[CentOS supervisor 安装与配置 （Laravel 队列示例）](https://learnku.com/articles/28919)
[supervisor 安装配置使用](https://learnku.com/laravel/t/2126/supervisor-installation-configuration-use)

## 代码设置
1. `QUEUE_CONNECTION=redis` 指定队列驱动程序的连接配置
## LINUX CENTOS 安装 SUPERVISOR
**安装supervisor很简单，通过easy_install就可以安装**
1. `yum install python-setuptools`
2. `easy_install supervisor`
3. `echo_supervisord_conf > /etc/supervisord.conf`

**配置**

`` vim /etc/supervisord.conf ``
```
例
[program:ity-notification]
process_name=%(program_name)s_%(process_num)02d
command=php /data/wwwroot/default/artisan queue:work redis --tries=3 --queue=notification
autostart=true
autorestart=true
user=root
numprocs=3
redirect_stderr=true
stdout_logfile=/data/wwwroot/default/storage/logs/queue-notification.log
```
```
启动 supervisor
supervisord -c /etc/supervisord.conf
```
```
关闭 supervisor
supervisorctl shutdown
```
```
重新载入配置
supervisorctl reload
```

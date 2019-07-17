# 原作
Forked from https://github.com/Git-Lofter/game-bot

# 魔改game-bot
适用于SSRPanel的telegram博彩机器人。sspanel的请访问原作。


# 环境要求
php7.0 + redis5.0 测试通过

# 如何使用

+ 导入mysql.sql至SSRPanel同一数据库，添加game,lottery数据表。
+ 导入lottery.sql至SSRPanel同一数据库，添加lottery数据表初始内容。
+ 配置config.php中的数据库和bot信息。
+ 修改telegram bot WebHook，访问：https://api.telegram.org/bot(BOT_TOKEN)/setWebhook?url=https://yoursite.com.
+ 宝塔监控 每天20：20访问一次cron.php。 php /www/wwwroot/您的安装目录/cron.php.
+ 宝塔监控 每一分钟访问一次open.php。 php /www/wwwroot/您的安装目录/open.php
+ 在网站上的个人设置里将微信号改为你的TG名称。

# 支付宝打赏
算了，不打了。
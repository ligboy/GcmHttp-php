GcmHttp-php
===========
其实已经有很多gcm php的库了，之所以重复造轮子，只是为了更好的适应国内gfw的屏蔽，方便添加代理。另外大家可以把GcmHttp::SEND改为我自己的一个反向代理（加州sf机房）: `http://gcm.ligboy.org/gcm/send` ,这样可以不用代理发送消息。当然我不敢完全保证100%可用性。

在发送消息上可以处理发送超过1000 registration_ids的消息，最大的限制，嘿嘿，需要你世界测试了。原理是先将registration_ids分割为不超过1000的二维数组，再使用curl_multi发送请求，最后在合并结果。

当消息接受registration_ids不超过1000时是使用curl发送。

### Usage:

```php
$api_key = 'AIzaSyDakOuMFHzLPhzkSiqVGRZ_UOvi62-RoAQ';
//$proxy = 'www.proxy.com:1080';
$proxy = null;

$gcm = new GcmHttp($api_key, $proxy);
$registration_ids = array('APA91bHddL9SxCzfVB6KFaWcXdvBEncgaplqZfgfTzneOEX0gLmyAtwAlHaw8hWqKNxO8Fx0d10DMNlu0VbV4r2eSk9kEzQxeYiLy1N2iIgEG1jtEN-gx0CDNt4N4bMaH8J0aHcz8TSycnqjMmUiDw757FpGmNvx43taXt7o3gei1p_o');
$data = array(
    'test' => 'dfsdfasdf',
    'name' => 'dfsdfsdfsdfsdfsdf',
    't' => time()
);
$result = $gcm->send($registration_ids, $data);
```
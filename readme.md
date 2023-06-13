# RevoltPHP

```php
<?php

use Exan\RevoltPhp\Revolt;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;

require './vendor/autoload.php';

$revolt = new Revolt(
    Loop::get(),
    'TOKEN',
    new NullLogger(),
);

$revolt
    ->withBonfire()
    ->bonfire->connect();
```

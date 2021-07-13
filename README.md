# Express PHP
*Like Express for Node.js, but in PHP.*

### Why?
Why not, I like programming.

## How to get started
A simple Hello world example.

```php
<?php

use React\EventLoop\Loop;
use ExpressPHP\Express;

require __DIR__ . '/vendor/autoload.php';

$loop = Loop::get();

$express = new Express($loop);
$express->get('/', function () {
    return 'Hello World!';
});
```

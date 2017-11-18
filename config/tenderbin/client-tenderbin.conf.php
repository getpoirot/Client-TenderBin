<?php

if (! isset($_SERVER['HTTP_HOST']))
    return [];

return [
    'server_url' => 'http://storage.'.$_SERVER['HTTP_HOST'],
];

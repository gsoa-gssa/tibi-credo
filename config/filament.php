<?php

return [
    "allowed_domains" => (array) explode(',', env('ALLOWED_DOMAINS', 'example.com,localhost')),
];

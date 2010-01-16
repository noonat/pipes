<?php

require_once __DIR__.'/../vendor/pecs/lib/pecs.php';
require_once __DIR__.'/../src/pipes.php';

require_once __DIR__.'/mock/headers.php';
require_once __DIR__.'/test_hash.php';
require_once __DIR__.'/test_request.php';
require_once __DIR__.'/test_response.php';
require_once __DIR__.'/test_route.php';
require_once __DIR__.'/test_pipes.php';

pecs\run();

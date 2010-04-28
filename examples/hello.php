<?php

require __DIR__.'/../pipes.php';

pipes\options()->views = __DIR__.'/views';

pipes\get('/', function() {
    return "Hello, world!";
});

pipes\get('/form', function() {
    return pipes\render('form.php');
});

pipes\post('/form', function($params) {
    if (empty($params->name)) {
        return pipes\render('form.php', array(
            'error' => 'You must enter a name'));
    } else {
        return pipes\redirect('/' . urlencode($params->name));
    }
});

pipes\get('/:name', function($params) {
    return "Hello, {$params->name}!";
});

pipes\run();

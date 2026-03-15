<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config
set('application', 'SobczakApp');
set('repository', 'git@github.com:kowal2499/sobczak-orders.git');
set('http_user', 'sobczak');
set('writable_mode', 'chmod');

set('sub_directory', 'app');

set('bin/php', '/usr/local/bin/php81');
set('bin/composer', '/opt/alt/php81/usr/bin/php -c /usr/local/php/php.ini /usr/local/bin/composer');


add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('sobczak_prod')
    ->setHostname('s7.zenbox.pl')
    ->set('remote_user', 'sobczak')
    ->set('deploy_path', '~/domains/app.sobczak.com.pl/deployer')
    ->set('branch', 'master')
;

host('sobczak_test')
    ->setHostname('s7.zenbox.pl')
    ->set('remote_user', 'sobczak')
    ->set('deploy_path', '~/domains/app-test.sobczak.com.pl')
    ->set('branch', 'test')
;

// Hooks

after('deploy:failed', 'deploy:unlock');

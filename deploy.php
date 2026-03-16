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

set('keep_releases', 3);

add('shared_files', ['.env.local']);
add('shared_dirs', ['var/log', 'var/cache', 'uploads']);
add('writable_dirs', ['var/log', 'var/cache', 'uploads']);

// Hosts
host('prod')
    ->setHostname('s7.zenbox.pl')
    ->set('remote_user', 'sobczak')
    ->set('deploy_path', '~/domains/app.sobczak.com.pl/deployer')
    ->set('branch', 'master')
;

host('test')
    ->setHostname('s7.zenbox.pl')
    ->set('remote_user', 'sobczak')
    ->set('deploy_path', '~/domains/app-test.sobczak.com.pl')
    ->set('branch', 'test')
    ->set('keep_releases', 2)
;

// Task: Synchronizacja modułów i grantów
task('app:sync_modules', function () {
    within('{{release_or_current_path}}', function () {
        run('{{bin/php}} bin/console app:module:register');
    });
})->desc('Synchronize modules and grants');

// Task: Synchronizacja tagów
task('app:sync_tags', function () {
    within('{{release_or_current_path}}', function () {
        run('{{bin/php}} bin/console app:tag-definition:create');
    });
})->desc('Synchronize tag definitions');

// Utworzenie symlink uploads
task('deploy:uploads_symlink', function () {
    run('cd {{deploy_path}}/current/public && rm -f uploads && ln -s ../../../shared/uploads uploads');
})->desc('Create uploads symlink in public directory');

before('deploy:symlink', 'database:migrate');      // Migracje przed przełączeniem symlink
after('database:migrate', 'app:sync_modules');     // Synchronizacja modułów po migracjach
after('app:sync_modules', 'app:sync_tags');        // Synchronizacja tagów po modułach
after('deploy:symlink', 'deploy:uploads_symlink'); // Symlink uploads po przełączeniu
after('deploy:failed', 'deploy:unlock');

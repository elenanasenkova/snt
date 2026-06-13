<?php

$uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Карта маршрутов: URI => [Контроллер, метод].
// Файл контроллера автоматически грузится из src/controllers/{Контроллер}.php.
// Добавить страницу = одна строка ниже.
$routes = [
    '/'                          => ['InfoController',   'index'],
    '/info'                      => ['InfoController',   'index'],

    '/login'                     => ['AuthController',   'login'],
    '/register'                  => ['AuthController',   'register'],
    '/logout'                    => ['AuthController',   'logout'],

    '/dashboard'                 => ['MemberController', 'dashboard'],
    '/feed'                      => ['FeedController',   'feed'],
    '/office'                    => ['OfficeController', 'office'],
    '/classifieds'               => ['MemberController', 'classifieds'],
    '/classifieds/delete'        => ['MemberController', 'deleteClassified'],
    '/finances'                  => ['MemberController', 'finances'],
    '/votes'                     => ['MemberController', 'votes'],
    '/protocols'                 => ['MemberController', 'protocols'],
    '/discussions'               => ['MemberController', 'discussions'],
    '/reports'                   => ['MemberController', 'reports'],
    '/notifications'             => ['MemberController', 'notifications'],
    '/tickets'                   => ['MemberController', 'tickets'],

    '/admin'                     => ['AdminController',         'dashboard'],
    '/admin/users'               => ['AdminController',         'users'],
    '/admin/users/export'        => ['AdminController',         'usersExport'],
    '/admin/moderation'          => ['AdminController',         'moderation'],

    '/admin/remind-fees'         => ['AdminFinanceController',  'remindFees'],
    '/admin/finances'            => ['AdminFinanceController',  'finances'],
    '/admin/finances/pay'        => ['AdminFinanceController',  'financesPay'],
    '/admin/finances/expense'    => ['AdminFinanceController',  'financesExpense'],
    '/admin/finances/elec-amount'=> ['AdminFinanceController',  'financesElecAmount'],
    '/admin/finances/elec-import'=> ['AdminFinanceController',  'financesElecImport'],
    '/download/expense'          => ['AdminFinanceController',  'downloadExpense'],

    '/admin/meetings'            => ['AdminVoteController',     'meetings'],
    '/admin/votes'               => ['AdminVoteController',     'votes'],
    '/admin/votes/close'         => ['AdminVoteController',     'votesClose'],
    '/admin/votes/delete'        => ['AdminVoteController',     'votesDelete'],
    '/admin/votes/export'        => ['AdminVoteController',     'votesExport'],

    '/admin/documents'           => ['AdminOfficeController',   'documents'],
    '/admin/tickets'             => ['AdminOfficeController',   'tickets'],
];

if (isset($routes[$uri])) {
    [$controller, $action] = $routes[$uri];
    require_once BASE_PATH . '/src/controllers/' . $controller . '.php';
    $controller::$action();
} else {
    http_response_code(404);
    renderTemplate('error_404', ['uri' => $uri]);
}

# Routing
Namespace: `\Warkhosh\Route`		

### AppRouter
*Важно: На данный момент роуты находятся в бета режиме и могут меняться по мере разработки!* 

### Examples:

Подготовка класса к работе через сбрасывание всех значений
```
Warkhosh\Route\Routing::start(function(Routing $route) { 
    // code ...
});
```


Группировка над дальнейшими инструкциями
```
$route->group(["prefix" => '/article'], function(Routing $route) {
    // code ...
});
```

Еще не готово: Группировка с указанием обязательного прохождения дополнительных инструкцией типа `middleware`
```
$route->group(["prefix" => "/article", "middleware" => 'auth'], function(Routing $route) {
    // code ...
});
```

Инструкции запросов
```
$route->any('/news', "Application\Admin\Modules\Video\Controller", ['except' => ['create', 'edit', 'update', destroy']]);
$route->get('/news', "Application\Admin\Modules\Video\Controller", ['only' => ['index']);
$route->post('/news', "Application\Admin\Modules\Video\Controller@index");
```

Передача параметров из урла в контроллер		
*Передача происходит в порядке расположения переменных*
```
$route->post('/news/{type}/{id:int}/show', "Application\Admin\Modules\Video\Controller@index");
```

Указание необязательного аргумента
```
$route->post('/news/{type:num?}', "Application\Admin\Modules\Video\Controller@index");
```

Выполнение своей инструкции		
*Контроллер и выборочные инструкции должы возразать boolean значение для определения результата выполнения*
```
$route->any('/about', function () {
    echo 'Hello World';

    // Если по техническим причинам нужно выйти из процесса или метода контроллера и продолжить работу поиска маршрута
    return RouteOption::SIGNAL_IGNORE_PROCESS;
});
```

Инструкция для прямого вызова контроллера
```
$route->run("Application\Admin\Modules\Video\Controller", ['only' => ['index']]);
```


Добавления задачи перед исполнением метода контроллера по текущему сценарию
```
$routing->addBeforeControllerEvent(function($route, $controller, $method, $args) {
    if (! is_null($controller)) {
        var_dump(["class" => get_class($controller)]);
    }
});
```

Указание перехватчика для HTTP ошибок в маршрутах
```
$routing->httpErrorHandler(function($code) {
    if ($code === 404) {
        die("Not Found");
    }
});
```

Общий пример использования
```
Warkhosh\Route\Routing::start(function(Routing $route) {
	$route->group(["prefix" => '/cp/video'], function(Routing $route) {

		$route->group(["prefix" => '/load'], function(Routing $route) {
			$route->get('/playlist', "\Application\Admin\Modules\Video\LoadCatalogController@drawPlaylist");
			$route->get('/catalog/{type}/{project}', "\Application\Admin\Modules\Video\LoadCatalogController@drawCatalog");
		});

		$route->any('/', "Application\Admin\Modules\Video\Controller");
	});
	
	$route->run("Application\Admin\404\Controller@index");
});
```
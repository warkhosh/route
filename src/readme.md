# Routing
Namespace: `\Warkhosh\Route`		

### AppRouter
*Важно: На данный момент роуты находятся в бета режиме и могут меняться по мере разработки!* 

### Examples:

Подготовка класса к работе через сбрасывание всех значений
```
Warkhosh\Route\Routing\Routing::start(function() { 
    // code ...
});
```


Группировка над дальнейшими инструкциями
```
Route::group(["prefix" => '/article'], function() {
    // code ...
});
```

Еще не готово: Группировка с указанием обязательного прохождения дополнительных инструкцией типа `middleware`
```
Route::group(["prefix" => "/article", "middleware" => 'auth'], function() {
    // code ...
});
```

Инструкции запросов
```
Route::any('/news', "Application\Admin\Modules\Video\Controller", ['except' => ['create', 'edit', 'update', destroy']]);
Route::get('/news', "Application\Admin\Modules\Video\Controller", ['only' => ['index']);
Route::post('/news', "Application\Admin\Modules\Video\Controller@index");
```

Передача параметров из урла в контроллер		
*Передача происходит в порядке расположения переменных*
```
Route::post('/news/{type}/{id:int}/show', "Application\Admin\Modules\Video\Controller@index");
```

Указание необязательного аргумента
```
Route::post('/news/{type:num?}', "Application\Admin\Modules\Video\Controller@index");
```

Выполнение своей инструкции		
*Контроллер и выборочные инструкции должы возразать boolean значение для определения результата выполнения*
```
Route::any('/about', function () {
     echo 'Hello World';
     
     return true; // Результат удачного выполнения, обязателен!
});
```

Инструкция для прямого вызова контроллера
```
Route::run("Application\Admin\Modules\Video\Controller", ['only' => ['index']]);
```

Общий пример использования
```
Route::start(function() {
	Route::group(["prefix" => '/cp/video'], function() {

		Route::group(["prefix" => '/load'], function() {
			Route::get('/playlist', "\Application\Admin\Modules\Video\LoadCatalogController@drawPlaylist");
			Route::get('/catalog/{type}/{project}', "\Application\Admin\Modules\Video\LoadCatalogController@drawCatalog");
		});

		Route::any('/', "Application\Admin\Modules\Video\Controller");
	});
	
	Route::run("Application\Admin\404\Controller", ['only' => ['index']]);
});
```
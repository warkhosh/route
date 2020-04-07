<?php

namespace Warkhosh\Route;

use Closure;
use Throwable;

class Routing
{
    /**
     * Список частей (path) текущего запроса
     *
     * @var null | array
     */
    protected $requestPart = null;

    /**
     * Тип запроса: GET, POST, PUT, PATCH, DELETE
     *
     * @var string
     */
    protected $requestMethod;

    /**
     * Название метода для сценария в контроллере.
     *
     * @note В конструкторе идет установка значений из конфига
     * @note Далее это значение может быть переопределено логикой роута если в условии указали конкретный
     *
     * @var null | string
     */
    protected $method;

    /**
     * Логическое значение результата выполнения контроллера.
     *
     * @note в классе допускается третье значение null - не отработал но getResult() всегда возращает boolean!
     *
     * @var boolean
     */
    protected $result;

    /**
     * Префикс для подставновки в начало URI
     *
     * @var string
     */
    protected $uriPrefix = '';

    /**
     * Страница по умолчанию
     *
     * @note не дописывается в условиях при несоответствии типа как строка
     *
     * @var string
     */
    protected $directoryIndex;

    /**
     * Удаление названия файла из путей правил и роутинга, для приведения путей к единому виду
     *
     * @var string
     */
    protected $removeDirectoryIndex = 'index.php';

    /**
     * @var \Warkhosh\Route\Request\RequestProviderInterface
     */
    private $requestProvider = \Warkhosh\Route\Request\RequestProvider::class;

    /**
     * Список анонимных функций установленые для отрабатывания перед исполнением метода контроллера
     *
     * @var array
     */
    protected $beforeControllerEvent = [];

    public function __construct()
    {
        if (is_string($this->requestProvider)) {
            $object = $this->requestProvider;
            $this->requestProvider = method_exists($object, 'getInstance') ? $object::getInstance() : new $object();
        }
    }

    /**
     * Подготовка класса к работе через сбрасывание всех значений.
     *
     * @param Closure | null $callback
     */
    public function start(?Closure $callback = null)
    {
        $this->setRequestParts(null);

        $this->method = null;
        $this->directoryIndex = null;
        $this->removeDirectoryIndex = 'index.php';
        $this->requestMethod = $this->requestProvider->getMethod();
        $this->result = null;

        if ($callback instanceof Closure) {
            $callback();
        }
    }

    /**
     * Название метода в сценарии контроллера.
     *
     * @return null|string
     */
    public function getMethodName()
    {
        return is_string($this->method) ? $this->method : null;
    }

    /**
     * Устанавливает название метода для сценария в контроллере
     *
     * @param string $method
     * @return void
     */
    public function setMethod($method = null)
    {
        if (! is_string($method)) {
            $this->method = $method;
        }
    }

    /**
     * Возращает части (path) от текущего запроса
     *
     * @return array
     */
    protected function getRequestParts()
    {
        if (is_null($this->requestPart)) {
            $this->requestPart = $this->requestProvider->getRequestParts();
        }

        return $this->requestPart;
    }

    /**
     * Метод особого выполнения сценария для не отработаного роута
     *
     * @param Closure|string $callback
     * @return void
     */
    public function notExecuted($callback = null)
    {
        if ($this->getResult() === false && $callback instanceof Closure) {
            echo call_user_func_array($callback, []);
        }
    }

    /**
     * Метод особого выполнения сценария после отработаного роута
     *
     * @param Closure|string $callback
     * @return void
     */
    public function isExecuted($callback = null)
    {
        if ($this->getResult() === true && $callback instanceof Closure) {
            echo call_user_func_array($callback, []);
        }
    }

    /**
     * Возращает результат отработки роутинга
     *
     * @return bool
     */
    public function getResult()
    {
        return is_null($this->result) ? false : $this->result;
    }

    /**
     * Устанавливаем новое значение частей текущего запроса
     *
     * @param array|null $requestParts
     */
    protected function setRequestParts(?array $requestParts = [])
    {
        if (is_null($requestParts) || is_array($requestParts)) {
            $this->requestPart = $requestParts;
        }
    }

    /**
     * Запускает проверку маршрута для любого типа запроса
     *
     * @note перед запуском идет проверка результата выполнения другого маршрута для избежания наложения сценариев
     *
     * @param string         $uri
     * @param Closure|string $callback
     * @param array          $options
     * @return void
     */
    public function any($uri = '', $callback = null, $options = [])
    {
        if (is_null($this->result)) {
            $this->validation($uri, $callback, $options);
        }
    }

    /**
     * Запускает проверку маршрута для типа запроса get
     *
     * @note перед запуском идет проверка результата выполнения другого маршрута для избежания наложения сценариев
     *
     * @param string         $uri
     * @param Closure|string $callback
     * @param array          $options
     * @return void
     */
    public function get($uri = '', $callback = null, $options = [])
    {
        if (is_null($this->result)) {
            if ($this->requestMethod === 'get') {
                $this->validation($uri, $callback, $options);
            }
        }
    }

    /**
     * Запускает проверку маршрута для типа запроса post
     *
     * @note перед запуском идет проверка результата выполнения другого маршрута для избежания наложения сценариев
     *
     * @param string         $uri
     * @param Closure|string $callback
     * @param array          $options
     * @return void
     */
    public function post($uri = '', $callback = null, $options = [])
    {
        if (is_null($this->result)) {
            if ($this->requestMethod === 'post') {
                $this->validation($uri, $callback, $options);
            }
        }
    }

    /**
     * Выволняет замыкание или контроллер с параметрами
     *
     * @note перед запуском идет проверка результата выполнения другого маршрута для избежания наложения сценариев
     *
     * @param Closure|string $callback
     * @param array          $options
     */
    public function run($callback = null, $options = [])
    {
        if (is_null($this->result)) {
            $this->runController($callback, $options);
        }
    }

    /**
     * Выволняет замыкание или контроллер с параметрами
     *
     * @param Closure|string $callback
     * @param array          $options
     * @param array          $args
     */
    protected function runController($callback = null, $options = [], $args = [])
    {
        $requestParts = $this->getRequestParts();

        try {
            if ($callback instanceof Closure) {
                if ($this->beforeController(null, null, $args) === Option::SIGNAL_CONTINUE) {
                    $signal = call_user_func_array($callback, $args);
                }

                $this->result = true; // отмечаем сценарий маршрута как выполненый для избежания выполнения других

            } elseif (is_string($callback)) {
                $part = explode("@", $callback);
                $class = new $part[0];

                if (isset($part[1]) && trim(isset($part[1])) != '') {
                    $method = $part[1];
                } else {
                    $method = $this->getDetectMethod($callback, $requestParts, $args);
                }

                // игнорируем\исключаем метод если он указан в исключениях
                if (array_key_exists('except', $options) && in_array($method, $options['except'])) {
                    $method = $this->method;
                }

                // проверяем метод на допустимые значения
                if (array_key_exists('only', $options) && ! in_array($method, $options['only'])) {
                    $method = $this->method;
                }

                if (! is_null($method)) {
                    if ($this->beforeController($class, $method, $args) === Option::SIGNAL_CONTINUE) {
                        $signal = call_user_func_array([$class, $method], $args);
                    }

                    $this->result = true; // отмечаем сценарий маршрута как выполненый для избежания выполнения других
                    $this->method = $method;
                }
            }

        } catch (Throwable $e) {
            // log...

            // Если определено название метода то значит было исклчение в нутри сценария.
            // Отмечаем его как выполненый что-бы не пойти в другие.
            if (isset($method)) {
                $this->result = true;
                //drawException($e, false);

            } else {
                trigger_error($e->getMessage());
            }
        }
    }

    /**
     * Метод авто определением сценария по значениям в URI если они не были явно указаны в роуте
     *
     * @param string $className
     * @param array  $requestPart - текущие части запроса
     * @param array  $args
     * @return null|string
     */
    protected function getDetectMethod($className, &$requestPart = [], &$args = [])
    {
        if (is_string($className)) {
            $method = null; // в начале метод не определен

            // определяем наличие символа собаки
            $isAt = (($pos = mb_strpos($className, "@", 0, 'UTF-8')) !== false);

            if ($isAt) {
                $part = explode("@", $className);
                $className = new $part[0];

                if (isset($part[1])) {
                    $method = $part[1];
                }
            }

            if (class_exists($className, true)) {
                // явно метод не указали, значит пытаемся его определить
                if (is_null($method)) {

                    // если собака не указана а частей больше нет
                    if ($isAt === false && count($requestPart) === 0) {
                        return 'index';
                    }

                    $requestPart = array_values($requestPart);
                    $count = count($requestPart);
                    $deleteKey = 0;

                    if ($count > 0) {
                        $count = $count - 1;

                        if ($requestPart[$count] === 'create') {
                            $method = 'create';
                            $deleteKey = 1;

                        } elseif ($requestPart[$count] === 'store') {
                            $method = 'store';
                            $deleteKey = 1;

                        } elseif ($requestPart[$count] === 'show' && is_numeric($requestPart[$count - 1])) {
                            $args[] = $requestPart[$count - 1];
                            $method = 'show';
                            $deleteKey = 2;

                        } elseif ($requestPart[$count] === 'edit' && is_numeric($requestPart[$count - 1])) {
                            $args[] = $requestPart[$count - 1];
                            $method = 'edit';
                            $deleteKey = 2;

                        } elseif ($requestPart[$count] === 'update' && is_numeric($requestPart[$count - 1])) {
                            $args[] = $requestPart[$count - 1];
                            $method = 'update';
                            $deleteKey = 2;

                        } elseif ($requestPart[$count] === 'destroy' && is_numeric($requestPart[$count - 1])) {
                            $args[] = $requestPart[$count - 1];
                            $method = 'destroy';
                            $deleteKey = 2;
                        }

                        if (! is_null($method) && method_exists($className, $method)) {
                            for ($i = 0; $i < $deleteKey; $i++) {
                                unset($requestPart[count($requestPart) - 1]);
                            }

                            return $method;
                        }
                    }

                    if (! is_string($this->method) && method_exists($className, $this->method)) {
                        return $this->method;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string         $uri
     * @param Closure|string $callback
     * @param array          $options
     */
    protected function validation($uri = '', $callback = null, $options = [])
    {
        $requestPart = $this->getRequestParts(); // значения текущего запроса ( ЧПУ )

        if ($conditions = Helper::find("?}", $uri)) {
            preg_match_all('/\{([\a-z\-\_\:]+?)\??\}/isu', $uri, $matches);

            foreach ($matches[1] as $key => $str) {
                $matches[1][$key] = "{!!{$str}!!}";
            }

            $uri = str_replace($matches[0], $matches[1], $uri);
        }

        $path = ! empty($uri) ? Helper::getPath($uri) : '';
        $file = ! empty($uri) ? Helper::getFile($uri) : '';

        if ($conditions && isset($matches)) {
            $path = str_replace($matches[1], $matches[0], $path);
        }

        $file = $file == $this->removeDirectoryIndex ? "" : $file;
        $file = empty($file) && is_string($this->directoryIndex) ? $this->directoryIndex : $file;
        $uri = Helper::start('/', ($path . (! empty($file) ? Helper::start('/', $file) : '')));

        $uri = $uri !== '/' ? $uri : ''; // если указали корень то убираем его что-бы совпало по explode()
        $parts = explode("/", Helper::getRemoveStart("/", $this->uriPrefix . $uri));
        $result = false;
        $args = [];

        // надо проверять при наличии значений ведь в проверке могли указать необязательные переменные
        if (count($requestPart) >= 1) {
            $result = true;

            // Список условий для роута
            foreach ($parts as $key => $fragment) {
                $itVariable = false;

                // проверка параметров в урле
                if (mb_substr($fragment, 0, 1) === '{' && mb_substr($fragment, -1) === "}") {
                    $fragment = mb_substr($fragment, 1, -1); // вырезаем скобки из фрагмента
                    $itVariable = true;
                }

                // если флрагмент это переменная
                if ($itVariable) {
                    $optional = (mb_substr($fragment, -1) === "?"); // Отдельная логика для не обязательной переменной

                    if ($optional) {
                        $fragment = mb_substr($fragment, 0, -1);
                        $tmp = explode(":", $fragment);

                        if (count($tmp) === 2 && ($tmp[1] === 'int' || $tmp[1] === 'num')) {
                            $int = isset($requestPart[$key]) ? intval($requestPart[$key]) : 0;

                            if ($tmp[1] === 'num' && isset($requestPart[$key])) {
                                $int = Helper::getNum($requestPart[$key]);
                            }

                            $args[] = $int;
                        } else {
                            $args[] = isset($requestPart[$key]) ? $requestPart[$key] : null;
                        }

                        unset($requestPart[$key]);
                        continue;

                    } elseif (array_key_exists($key, $requestPart)) {
                        $tmp = explode(":", $fragment);

                        if (count($tmp) === 2 && ($tmp[1] === 'int' || $tmp[1] === 'num')) {
                            $int = isset($requestPart[$key]) ? intval($requestPart[$key]) : 0;

                            if ($tmp[1] === 'num' && isset($requestPart[$key])) {
                                $int = Helper::getNum($requestPart[$key]);
                            }

                            $args[] = $int;
                        } else {
                            $args[] = $requestPart[$key];
                        }

                        unset($requestPart[$key]);
                        continue;
                    }

                } elseif (! $itVariable && array_key_exists($key, $requestPart)) {
                    if (preg_match("/^{$fragment}$/iu", $requestPart[$key])) {
                        unset($requestPart[$key]);
                        continue;
                    }
                }

                // Сюда попадают при условии что условия по роуту не подошли к текущему запросу
                $result = false;
                break;
            }
        }

        if ($result === true) {
            // записываем получившийся список частей текущего запроса для последующих проверок с окончания текущих
            $this->setRequestParts($requestPart);
            $this->runController($callback, $options, $args);
        }
    }

    /**
     * Группировка над дальнейшими инструкциями
     *
     * @param array          $attributes
     * @param Closure|string $callback
     * @return void
     */
    public function group(array $attributes, $callback = null)
    {
        $addPrefix = '';
        $runGroup = true;

        foreach ($attributes as $key => $row) {

            // если передали префикс, дописываем его всем маршрутам в начале
            if ($key === "prefix") {
                $addPrefix = Helper::start("/", $row); // записываем какой префикс прибавили
                $this->uriPrefix .= $addPrefix;
            }

            //if ($key === "middleware") {
            //    if ($row instanceof Closure) {
            //        $middleware = $row;
            //    } else {
            //        $middleware = is_array($row) ? $row : RouteHelper::explode(",", $row, ['']);
            //    }
            //
            //    $middleware = new Middleware($middleware);
            //    $runGroup = $middleware->trigger()->getResult();
            //}
        }

        try {
            if ($runGroup) {
                if ($callback instanceof Closure) {
                    $callback();
                }
            }

            if (isset($middleware)) {
                $middleware->terminate();
                unset($middleware);
            }

        } catch (Throwable $e) {
            //Log::warning($e);
            trigger_error($e->getMessage());
        }

        // по завершению выполнения группы убираем прибавленый префикс
        $this->uriPrefix = Helper::getRemoveEnding($addPrefix, $this->uriPrefix);
    }

    /**
     * Публичный метод для добавления задачи перед исполнением метода контроллера
     *
     * @param Closure $function
     * @return $this
     */
    public function addBeforeControllerEvent(Closure $function)
    {
        if ($function instanceof Closure) {
            $this->beforeControllerEvent[] = $function;
        }

        return $this;
    }

    /**
     * Метод (событие) запускает (если указали) задачи перед началом работы контроллера
     *
     * @param string | null $controllerObject
     * @param string | null $method
     * @param array         $args
     * @return int
     */
    public function beforeController($controllerObject = null, ?string $method = null, array $args = [])
    {
        try {
            foreach ($this->beforeControllerEvent as $function) {
                // всегда первым параметром передаем класс контроллера за ним метод и аргументы
                $result = $function($controllerObject, $method, $args);

                if (key_exists($result, Option::$signals)) {
                    return $result;
                }
            }

        } catch (Throwable $e) {
            return Option::SIGNAL_STOP;
        }

        return Option::SIGNAL_CONTINUE;
    }
}
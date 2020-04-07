<?php

namespace Warkhosh\Route\Request;

use Warkhosh\Route\Helper;

class RequestProvider implements RequestProviderInterface
{
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
     * Возвращает название используемого метода для запроса текущей страницы
     *
     * @return string
     */
    public function getMethod()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $requestMethod = mb_strtolower($_SERVER['REQUEST_METHOD']);

            // По общему тренду поддерживаю передачу POST данных с переменной _method
            if ($requestMethod === 'post' && isset($_POST['_method']) && $_POST['_method'] != '') {
                $customMethod = mb_strtolower(trim($_POST['_method']));

                if (in_array($customMethod, ['put', 'patch', 'delete'])) {
                    return $customMethod;
                }
            }

            return $requestMethod;
        }

        return 'get';
    }

    /**
     * Возращает части (path) от текущего запроса
     *
     * @return array
     */
    public function getRequestParts()
    {
        $requestPath = Helper::getPath(Helper::getRequestUri(false));
        $requestFile = "";

        if (! empty($requestFile)) {
            $file = $requestFile == $this->removeDirectoryIndex ? "" : $requestFile;
            $file = empty($file) && is_string($this->directoryIndex) ? $this->directoryIndex : $file;
            $requestPath .= ! empty($file) ? "/{$file}" : '';
        }

        $requestPath = preg_replace('/(?:\/)+$/u', '', $requestPath);

        return explode("/", preg_replace('/^(?:\/)+/u', '', $requestPath));
    }
}
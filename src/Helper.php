<?php

namespace Warkhosh\Route;

class Helper
{
    /**
     * Регистрозависимый поиск первого вхождения символа в строке с возвратом результата
     *
     * @note Первый символ стоит на позиции 0, позиция второго 1 и так далее.
     * @param string | array $needles - строка, поиск которой производится в строке $str
     * @param string         $str     - строка в которой ищем $needles
     * @param int            $offset
     * @return bool
     */
    public static function find($needles = null, $str = '', $offset = 0)
    {
        if (static::findPos($needles, $str, $offset) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Регистрозависимый поиск первого вхождения символа в строке с возвратом номера позиции символа или false
     *
     * @note Первый символ стоит на позиции 0, позиция второго 1 и так далее.
     * @param string | array $needles - строка, поиск которой производится в строке $str
     * @param string         $str     - строка в которой ищем $needles
     * @param integer        $offset
     * @return integer|bool
     */
    public static function findPos($needles = null, $str = '', $offset = 0)
    {
        foreach ((array)$needles as $needle) {
            if (($pos = mb_strpos($str, $needle, $offset, 'UTF-8')) !== false) {
                return $pos;
            }
        }

        return false;
    }

    /**
     * Возвращает путь без файла и query параметров
     *
     * @note метод следит что-бы значения начинались со слэша
     * @param string $uri
     * @return string
     */
    static public function getPath($uri = '')
    {
        $uri = parse_url(rawurldecode(trim((string)$uri)), PHP_URL_PATH);
        $info = pathinfo($uri);

        if (isset($info['extension'])) {
            $uri = $info['dirname'];
        } else {
            $info['dirname'] = isset($info['dirname']) ? "{$info['dirname']}/" : '';

            // Данное решение фиксит баг при обрабатке кривого урла, когда в конце get параметров идет слэш или слеши
            // example: http://photogora.ru/background/muslin&filter_category=126/
            $tmp = "{$info['dirname']}{$info['basename']}";
            $uri = rtrim($uri, '/') == $tmp ? $uri : $tmp;
        }

        return static::start("/", $uri);
    }

    /**
     * Устанавливает начало строки на указанное с проверкой на наличие такого значения
     *
     * @param string $prefix
     * @param string $str
     * @return string
     */
    public static function start(string $prefix, ?string $str): string
    {
        $quoted = preg_quote($prefix, '/');
        $str = is_string($str) ? $str : "";

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $str);
    }


    /**
     * Закрывает строку заданным значением с проверкой на наличие такого значения
     *
     * @param string $prefix
     * @param string $str
     * @return string
     */
    public static function ending(string $prefix, ?string $str): string
    {
        $quoted = preg_quote($prefix, '/');
        $str = is_string($str) ? $str : "";

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $str) . $prefix;
    }

    /**
     * Убирает указное значения из начала строки
     *
     * @param string|array $prefix
     * @param string       $str
     * @return string
     */
    static public function getRemoveStart($prefix = '', string $str = '')
    {
        if (gettype($prefix) === 'array') {
            foreach ($prefix as $text) {
                $str = preg_replace('/^(?:' . preg_quote($text, '/') . ')+/u', '', $str);
            }

            return $str;
        }

        if (gettype($prefix) === 'string') {
            return preg_replace('/^(?:' . preg_quote($prefix, '/') . ')+/u', '', $str);
        }

        return $str;
    }

    /**
     * Возвращает информацию о файле по указанному пути
     *
     * @note в случае не удачи вернет пустую строку
     *
     * @param string $str
     * @return string
     */
    static public function getFile($str = '')
    {
        $str = parse_url($str, PHP_URL_PATH);
        $info = pathinfo($str);
        $file = '';

        // если есть расширение файла то пытаемся отдельно установить параметры файла
        if (isset($info['extension']) &&
            isset($info['filename']) &&
            ! empty($info['extension']) &&
            ! empty($info['filename'])) {
            $file = "{$info['filename']}.{$info['extension']}";
        }

        unset($info);

        return $file;
    }

    /**
     * Преобразование переданого значения в положительное целое цело.
     * В случае неудачи присвоит ему второе значение
     *
     * @param int  $num     - проверяемое значение
     * @param int  $default - значение при неудачной проверке
     * @param bool $strict  - флаг для преобразования дополнительных значений типа "on|off|no|yes" в число
     * @return int
     */
    static public function getNum($num = 0, $default = 0, $strict = true)
    {
        if (is_numeric($num) || is_string($num) || is_float($num) || is_double($num) || is_bool($num)) {
            if (! $strict) {
                switch (strtolower(trim($num))) {
                    case '1':
                    case 'true':
                    case 'on':
                    case 'yes':
                        return 1;
                        break;

                    case '0':
                    case 'false':
                    case 'off':
                    case 'no':
                        return 0;
                        break;
                }
            }

            $num = intval($num);
            return $num >= 0 ? $num : $default;
        }

        return intval($default);
    }

    /**
     * Удаляет пробелы из начала и конца строки (или другие символы при передачи их вторым параметром )
     *
     * @note \x0B вертикальная табуляция,
     *
     * @param string $str
     * @param string $removeChar - список символов для удаления
     * @return string
     */
    static public function trim($str = '', $removeChar = " \t\n\r\0\x0B")
    {
        if (! is_string($str)) {
            $str = strval($str);
        }

        $str = trim($str, (string)$removeChar);
        //$str = trim($str, chr(194) . chr(160)); // работает только в ASCII а иначе это &#171;

        // удаляем управляющие ASCII-символы с начала и конца $binary (от 0 до 31 включительно)
        return trim($str, "\x00..\x1F");
    }

    /**
     * Разбивает строку по разделителю и дополнительно производит удаление пустых значений;
     *
     * @param string $delimiter - разделитель
     * @param string $str       - строка
     * @param array  $deleted   - массив значений которые надо удалить
     * @return array
     */
    static public function explode($delimiter = ',', string $str = '', $deleted = ['', 0, null, 'null'])
    {
        if (! is_null($deleted)) {
            $arr = explode($delimiter,  static::trim($str));

            return array_diff($arr, (array)$deleted);

        } else {
            return explode($delimiter,  static::trim($str));
        }
    }

    /**
     * Убирает указное значения из конца строки
     *
     * @param string|array $prefix
     * @param string       $str
     * @return string
     */
    static public function getRemoveEnding($prefix = '', string $str = '')
    {
        if (gettype($prefix) === 'array') {
            foreach ($prefix as $text) {
                $str = preg_replace('/(?:' . preg_quote($text, '/') . ')+$/u', '', $str);
            }

            return $str;
        }

        if (gettype($prefix) === 'string') {
            return preg_replace('/(?:' . preg_quote($prefix, '/') . ')+$/u', '', $str);
        }

        return $str;
    }

    /**
     * Безопасное преобразование строки в указаную кодировку если она таковой не является
     *
     * @param string $str      - строка, для которой требуется определить кодировку
     * @param string $encoding - список возможных кодировок
     * @return string
     */
    static public function getTransformToEncoding($str = '', $encoding = 'UTF-8')
    {
        if (! mb_check_encoding($str, $encoding)) {
            $str = mb_convert_encoding($str, $encoding);
            $str = @iconv(mb_detect_encoding($str, mb_detect_order(), false), "{$encoding}//IGNORE", $str);
        }

        return $str;
    }

    /**
     * Возвращает протокол с его префиксами для домена.
     *
     * @return string
     */
    static public function getServerProtocol()
    {
        if ((! empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
            (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
            (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) {
            return 'https://';
        }

        return 'http://';
    }

    /**
     * Возвращает имя хоста, на котором выполняется текущий скрипт
     *
     * @return string
     */
    static public function getServerName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    }

    /**
     * Возвращает путь текущего запроса
     *
     * @param bool $query - флаг для включения\выключения query параметров запроса
     * @return string
     */
    static public function getRequestUri($query = true)
    {
        $requestUri = '/';

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $requestUri = (string)$_SERVER['REQUEST_URI'];
            $requestUri = static::getTransformToEncoding($requestUri, "UTF-8");
        }

        $requestUri = trim($requestUri, " \t\n\r\0\x0B");
        $requestUri = trim($requestUri, "\x00..\x1F");

        // дополнительные преобразования плохих значений мы уже делаем и пишем в CMF_REQUEST_URI
        if (($uri = ltrim($requestUri, "/")) !== $requestUri) {
            $requestUri = "/{$uri}";
        }

        if (! $query && $requestUri != '') {
            // Обязательно прописываем протокол и сервер иначе два первых слеша будут приняты за протокол!
            $url = static::getServerProtocol() . static::getServerName() . $requestUri;
            return parse_url($url, PHP_URL_PATH);
        }

        return $requestUri;
    }
}
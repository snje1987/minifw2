<?php

namespace Org\Snje\Minifw;

/**
 * 自定义的异常类，只在本程序内抛出和捕获
 */
class Exception extends \Exception {

    /**
     *
     * @param mixed $message 错误消息，如果是数组或对象，则会使用print_r转换
     * @param int $code 错误码
     * @param \Exception $previous 触发者
     */
    public function __construct($message = "", $code = -1, \Exception $previous = null) {
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        parent::__construct($message, $code, $previous);
    }

}

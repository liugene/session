<?php

namespace linkphp\session;

use framework\Exception;

use Config;

class Session
{

    private $config = [];

    private $prefix = '';

    public function import($config)
    {
        if(is_array($config) && empty($this->config)) $this->config = $config;
    }
    
    public function __construct()
    {
        if(empty($this->config)){
            $this->config = Config::get('session.');
        }
        /**
         * 开启session机制
         */
        if($this->config['session_on']){
            session_start();
        }

        if (isset($this->config['prefix']) && ('' === $this->prefix || null === $this->prefix)) {
            $this->prefix = $this->config['prefix'];
        }

        if (isset($this->config['var_session_id']) && isset($_REQUEST[$this->config['var_session_id']])) {
            session_id($_REQUEST[$this->config['var_session_id']]);
        } elseif (isset($this->config['id']) && !empty($this->config['id'])) {
            session_id($this->config['id']);
        }

        if (isset($this->config['name'])) {
            session_name($this->config['name']);
        }

        if (isset($this->config['path'])) {
            session_save_path($this->config['path']);
        }

        if (isset($this->config['domain'])) {
            ini_set('session.cookie_domain', $this->config['domain']);
        }

        if (isset($this->config['expire'])) {
            ini_set('session.gc_maxlifetime', $this->config['expire']);
            ini_set('session.cookie_lifetime', $this->config['expire']);
        }

        if (isset($this->config['secure'])) {
            ini_set('session.cookie_secure', $this->config['secure']);
        }

        if (isset($this->config['httponly'])) {
            ini_set('session.cookie_httponly', $this->config['httponly']);
        }

        if (!empty($this->config['type'])) {
            // 读取session驱动
            $class = false !== strpos($this->config['type'], '\\') ? $this->config['type'] : '\\linkphp\\session\\storage\\' . ucwords($this->config['type']);

            // 检查驱动类
            if (!class_exists($class) || !session_set_save_handler(new $class($this->config))) {
                throw new Exception('error session handler:' . $class, $class);
            }
        }
    }

    /**
     * 设置或者获取session作用域（前缀）
     * @param string $prefix
     * @return string|void
     */
    public function prefix($prefix = '')
    {
        if (empty($prefix) && null !== $prefix) {
            return $this->prefix;
        } else {
            $this->prefix = $prefix;
        }
    }

    /**
     * session设置
     * @param string        $name session名称
     * @param mixed         $value session值
     * @param string|null   $prefix 作用域（前缀）
     * @return void
     */
    public function set($name, $value = '', $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if (strpos($name, '.')) {
            // 二维数组赋值
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } elseif ($prefix) {
            $_SESSION[$prefix][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }

    /**
     * session获取
     * @param string        $name session名称
     * @param string|null   $prefix 作用域（前缀）
     * @return mixed
     */
    public function get($name = '', $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if ('' == $name) {
            // 获取全部的session
            $value = $prefix ? (!empty($_SESSION[$prefix]) ? $_SESSION[$prefix] : []) : $_SESSION;
        } elseif ($prefix) {
            // 获取session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                $value               = isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                $value = isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                $value               = isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                $value = isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
        return $value;
    }

    /**
     * 删除session数据
     * @param string|array  $name session名称
     * @param string|null   $prefix 作用域（前缀）
     * @return void
     */
    public function delete($name, $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if (is_array($name)) {
            foreach ($name as $key) {
                $this->delete($key, $prefix);
            }
        } elseif (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    }

    /**
     * 清空session数据
     * @param string|null   $prefix 作用域（前缀）
     * @return void
     */
    public function clear($prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if ($prefix) {
            unset($_SESSION[$prefix]);
        } else {
            $_SESSION = [];
        }
    }

    /**
     * 判断session数据
     * @param string        $name session名称
     * @param string|null   $prefix
     * @return bool
     */
    public function has($name, $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if (strpos($name, '.')) {
            // 支持数组
            list($name1, $name2) = explode('.', $name);
            return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
        } else {
            return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
        }
    }

    /**
     * 销毁session
     * @return void
     */
    public static function destroy()
    {
        if (!empty($_SESSION)) {
            $_SESSION = [];
        }
        session_unset();
        session_destroy();
    }

    /**
     * 重新生成session_id
     * @param bool $delete 是否删除关联会话文件
     * @return void
     */
    public function regenerate($delete = false)
    {
        session_regenerate_id($delete);
    }

    /**
     * 暂停session
     * @return void
     */
    public static function pause()
    {
        // 暂停session
        session_write_close();
    }
    
}
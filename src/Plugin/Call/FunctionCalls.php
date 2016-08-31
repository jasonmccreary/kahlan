<?php
namespace Kahlan\Plugin\Call;

use Kahlan\Suite;
use Kahlan\Plugin\Monkey;
use Kahlan\Plugin\Call\Message\FunctionMessage;

class FunctionCalls
{
    /**
     * Logged calls.
     *
     * @var array
     */
    protected static $_logs = [];

    /**
     * Current index of logged calls per reference.
     *
     * @var array
     */
    protected static $_index = 0;

    /**
     * Message invocation.
     *
     * @var array
     */
    protected $_message = [];

    /**
     * Reference.
     *
     * @var array
     */
    protected $_reference = null;

    /**
     * The Constructor.
     *
     * @param string $reference A fully-namespaced function name.
     */
    public function __construct($reference)
    {
        Suite::register(Suite::hash($reference));
        $this->_message = new FunctionMessage(['name' => $reference]);
    }

    /**
     * Return the message instance.
     *
     * @return object The message instance.
     */
    public function message()
    {
        return $this->_message;
    }

    /**
     * Logs a call.
     *
     * @param mixed  $reference A fully-namespaced function name.
     * @param string $params    The parameters name.
     */
    public static function log($reference, $params)
    {
        static::$_logs[] = [
            'name'   => $reference,
            'params' => $params
        ];
    }

    /**
     * Returns Logged calls.
     */
    public static function logs()
    {
        return static::$_logs;
    }

    /**
     * Gets/sets the find index
     *
     * @param  integer $index The index value to set or `null` to get the current one.
     * @return integer        Return founded log call.
     */
    public static function lastFindIndex($index = null)
    {
        if ($index !== null) {
            static::$_index = $index;
        }
        return static::$_index;
    }

    /**
     * Finds a logged call.
     *
     * @param  object        $message   The function message.
     * @param  interger      $index     Start index.
     * @return array|false              Return founded log call.
     */
    public static function find($message, $index = 0, $times = 0)
    {
        $success = false;
        $params = [];

        $count = count(static::$_logs);

        for ($i = $index; $i < $count; $i++) {
            $log = static::$_logs[$i];

            if (!$message->match($log)) {
                continue;
            }
            $params[] = $log['params'];

            if (!$message->matchParams($log['params'])) {
                continue;
            }

            $times -= 1;
            if ($times < 0) {
                static::$_index = $i + 1;
                $success = true;
                break;
            } elseif ($times === 0) {
                $next = static::find($message, $i + 1);
                if ($next['success']) {
                    $params = array_merge($params, $next['params']);
                    $success = false;
                } else {
                    $success = true;
                    static::$_index = $i + 1;
                }
                break;
            }
        }
        $index = static::$_index;
        return compact('log', 'success', 'params', 'index');
    }

    /**
     * Clears the registered references & logs.
     */
    public static function reset()
    {
        static::$_logs = [];
        Suite::reset();
    }
}

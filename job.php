<?php
namespace Job;

class Http
{
    const URL = "http://zoon.ru/job.php";

    /**
     * @param string $name
     * @param string $contact
     * @param string $cv
     * @return array
     */
    public static function getData($name, $contact, $cv)
    {
        $query = array(
            'name'    => ($name) ? $name : 'false',
            'contact' => ($contact) ? $contact : 'false',
            'cv'      => ($cv) ? $cv : 'false'
        );

        $url = self::URL . '?' . http_build_query($query);
        $data = file_get_contents($url);

        return json_decode($data, true);
    }
}

class StackMachine
{
    const CMD_DUP  = 'DUP';
    const CMD_DEC  = 'DEC';
    const CMD_IF   = 'IF';
    const CMD_DROP = 'DROP';
    const CMD_GO   = 'GO';
    const CMD_MOVE = 'MOVE';
    const CMD_CHR  = 'CHR';
    const CMD_OUT  = 'OUT';
    const CMD_MLT  = '*';
    const CMD_ADD  = '+';

    /**
     * @var \SplStack
     */
    private $stack;

    /**
     * @var
     */
    private $task;

    /**
     * @var \SplStack
     */
    private $out;

    /**
     * @var int
     */
    private $IP = 0;

    /**
     * @param array $task
     * @param \SplStack $stack
     */
    public function __construct(array $task = [], \SplStack $stack = null)
    {
        if (!$stack) {
            $stack = new \SplStack();
        }

        $this->stack = $stack;
        $this->task = $task;
        $this->out = new \SplStack();
    }

    /**
     * @return \SplStack
     */
    public function run()
    {
        $result = true;
        while ($result && isset($this->task[$this->IP])) {
            $result = $this->cmd($this->task[$this->IP]);
        }

        return $this->out;
    }

    /**
     * @param string|int $command
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function cmd($command)
    {
        $ipOffset = 1;

        if (is_numeric($command)) {
            $this->stack->push($command);
        } elseif ($this->stack->isEmpty()) {
            return false;
        } else switch ($command) {
            case self::CMD_DUP:
                $p = $this->stack->pop();
                $this->stack->push($p);
                $this->stack->push($p);
                break;

            case self::CMD_DEC:
                $p = $this->stack->pop();
                $this->stack->push($p - 1);
                break;

            case self::CMD_IF:
                $q = $this->stack->pop();
                $p = $this->stack->pop();
                if ($p == 0) {
                    $ipOffset = $q - 3;
                }
                break;

            case self::CMD_DROP:
                $this->stack->pop();
                break;

            case self::CMD_GO:
                $p = $this->stack->pop();
                $ipOffset = $p + 2;
                break;

            case self::CMD_MOVE:
                $q = $this->stack->pop();
                $p = $this->stack->pop();

                for ($buf = new \SplStack(); $q > 0; $q--) {
                    $buf->push($this->stack->pop());
                }
                $this->stack->push($p);
                while (!$buf->isEmpty()) {
                    $this->stack->push($buf->pop());
                }
                break;

            case self::CMD_CHR:
                $p = $this->stack->pop();
                $this->stack->push(chr($p-3));
                break;

            case self::CMD_OUT:
                $p = $this->stack->pop();
                $this->out->unshift($p);
                break;

            case self::CMD_MLT:
                $p = $this->stack->pop();
                $q = $this->stack->pop();
                $this->stack->push($p * $q);
                break;

            case self::CMD_ADD:
                $p = $this->stack->pop();
                $q = $this->stack->pop();
                $this->stack->push($p + $q);
                break;

            default:
                throw new \InvalidArgumentException('Invalid command - ' . $command);
        }

        $this->IP = $this->IP + $ipOffset;
        return true;
    }

}

class TaskRunner
{
    /**
     * @var array
     */
    private $tasks;

    /**
     * @param array $tasks
     */
    public function __construct(array $tasks = [])
    {
        $this->tasks = $tasks;
    }

    /**
     * @return \SplStack
     */
    public function run()
    {
        $stack = new \SplStack();

        foreach ($this->tasks as $task) {
            $machine = new StackMachine($task, $stack);
            $stack = $machine->run();
        }

        return $stack;
    }
}
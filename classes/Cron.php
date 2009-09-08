<?php

class scbCron
{
	protected $hook;
	protected $schedule;
	protected $interval;
	protected $time;
	protected $callback_args;

	/* 
		Create a new cron job
		$args:
			string $action OR callback $callback
			string $schedule OR number $interval
			array $callback_args (optional)
	 */
	function __construct($file, $args, $debug = false)
	{
		$this->_set_args($args);

		register_activation_hook($file, array($this, 'reset'));
		register_deactivation_hook($file, array($this, 'unschedule'));

		add_filter('cron_schedules', array($this, '_add_timing'));

		if ( $debug )
			self::debug();
	}

	/* Change the interval of the cron job
	 * $args:
	 *	- string $schedule OR number $interval
	 *	- time (optional)
	 */
	function reschedule($args)
	{
		extract($args);

		if ( $schedule && $this->schedule != $schedule )
			$this->schedule = $schedule;
		elseif ( $interval && $this->interval != $interval )
		{
			$this->schedule = $interval . 'secs';
			$this->interval = $interval;
		}

		$this->time = $time;

		$this->reset();
	}

	// Reset the schedule
	function reset()
	{
		$this->unschedule();
		$this->schedule();
	}

	// Set the cron job
	function schedule($time = 0)
	{
		if ( ! $this->time )
			$this->time = time();

		wp_schedule_event($this->time, $this->schedule, $this->hook, $this->callback_args);
	}

	// Clear the cron job
	function unschedule()
	{
		wp_clear_scheduled_hook($this->hook, $this->callback_args);
	}

	// Execute the job now
	function do_now()
	{
		do_action($this->hook);
	}

	function do_once($delay = 0)
	{
		wp_schedule_single_event(time() + $delay, $this->hook, $this->callback_args);
	}

	// Display current cron jobs
	function debug()
	{
		add_action('admin_footer', array(__CLASS__, '_debug'));
	}

	function _debug()
	{
		if ( ! current_user_can('manage_options') )
			return;

		echo "<pre>";
		print_r(get_option('cron'));
		echo "</pre>";
	}


// _____PRIVATE METHODS_____


	function _add_timing($schedules)
	{
		if ( isset($schedules[$this->schedule]) )
			return $schedules;

		$schedules[$this->schedule] = array(
			'interval' => $this->interval,
			'display' => $this->interval . ' seconds'
		);

		return $schedules;
	}

	private function _set_args($args)
	{
		extract($args);

		// Set hook
		if ( $callback )
		{
			$this->hook = self::_callback_to_string($callback);

			add_action($this->hook, $callback);
		}
		elseif ( $action )
			$this->hook = $action;
		else
			trigger_error('$action OR $callback not set', E_USER_WARNING);

		// Set schedule
		if ( $interval )
		{
			$this->schedule = $interval . 'secs';
			$this->interval = $interval;
		}
		elseif ( $schedule )
			$this->schedule = $schedule;
		else
			trigger_error('$schedule OR $interval not set', E_USER_WARNING);

		$this->callback_args = (array) $callback_args;
	}

	private static function _callback_to_string($callback)
	{
		if ( !is_array($callback) )
			$str = $callback;
		elseif ( !is_string($callback[0]) )
			$str = get_class($callback[0]) . '_' . $callback[1];
		else
			$str = $callback[0] . '::' . $callback[1];

		$str .= '_hook';

		return $str;
	}
}

/*
Doesn't require $args

function really_clear_scheduled_hook($name)
{
	$crons = _get_cron_array();

	foreach ( $crons as $timestamp => $hook )
		if ( $hook == $name )
			unset($crons[$hook]);

	_set_cron_array( $crons );
}
*/


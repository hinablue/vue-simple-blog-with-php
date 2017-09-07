<?php

namespace Blog\Vendor;

class Di {
	public function __construct() {
	}

	public function set($name, $value) {
		if (is_callable($value)) {
			$this->$name = call_user_func($value, $this);
		} else {
			$this->$name = $value;
		}
		return $this;
	}

	public function __get($name) {
		try {
			if (property_exists($this, $name)) {
				return $this->$name;
			}
			throw \Exception('Property \''.$name.'\' does not exists.');
		} catch (\Exception $e) {
			throw $e;
		}
	}
}

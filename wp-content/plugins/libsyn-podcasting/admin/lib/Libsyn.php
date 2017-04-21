<?php
abstract class Libsyn implements Iterator, Countable, ArrayAccess  {

	public $text_dom = "libsyn-nmp";
	protected $plugin_name = "Libsyn/Wordpress";
	protected $plugin_version = "0.9.6.8";
	protected $api_table_name = "libsyn_podcast_plugin";
	protected $api_base_uri = "https://api.libsyn.com";
	protected $minimum_php_version = 5.3;
	protected $recommended_php_version = 5.4;
	protected $max_php_version = 7.2;
	protected $wp_base_dir;
	protected $plugin_base_dir;
	protected $plugin_path;
	protected $plugin_admin_dir;
	protected static $__context = NULL;
	protected $__serviceName = NULL;
	
	/**
	 * Logger should only be used when PHP v 5.4+
	 *
	 * @var bool|Libsyn\Service\Logger
	 */
	public  $logger;

	/**
	 * Internal mapping of iterable fields
	 *
	 * When the {@link __construct()} method is fired off it creates a mapping of properties
	 * on the class.  These properties define what fields can be iterated over via the 
	 * ArrayAccess interface, as well as which fields are exported with the {@link __toArray()}
	 * method.  This method is used internally by {@link Base} and should not be changed.
	 *
	 * @var array
	 */
	private $__map = array();
	
	/**
	 * Internal mapping of fields to ignore from iteration
	 *
	 * When this class is iterated over or exported with {@link __toArray()} any properties
	 * that are identified will be ignored, as if they were not fields in the array.
	 *
	 * @var array
	 */
	protected $__ignore = array();

	/**
	 * Stored looksup for key conversion
	 *
	 * We keep track of various camel case and back again conversions so that subsequent uses
	 * can do faster lookups.  This is static so that it lasts longer then the object making
	 * the particular call, which should provide us with some performance benefit on larger
	 * data sets.
	 *
	 * @var array
	 */
	protected static $__lookups = array();
	
	public function getVars() {
		$this->wp_time = time();
		$dirs = explode('/', dirname( __FILE__ ).'/');
		for($z=0;$z<(count($dirs)-6);$z++) $this->wp_base_dir .= $dirs[$z].'/'; 
		for($z=0;$z<(count($dirs)-3);$z++) $this->plugin_base_dir .= $dirs[$z].'/';
		$libsyn_dir = $dirs[(count($dirs)-4)];
		$this->plugin_path = $libsyn_dir.'/'.$libsyn_dir.'.php';
		$this->plugin_admin_dir = dirname( __FILE__ ).'/';
		//if logger create logger instance
		if(class_exists('Libsyn\Service\Logger')){
			$logFilePath = $this->plugin_admin_dir . $this->text_dom.'.log';
			// if(!file_exists($logFilePath)){
				// touch($logFilePath);
			// }
			$data = '';
			if (file_exists($logFilePath)){
				//attempt to make writable.
				if(!is_writable($logFilePath)) @chmod($logFilePath, 0777);
			} else {
				$newFile= fopen($logFilePath, 'w+');
				fwrite($newFile, $data);
				fclose($newFile);
				chmod($logFilePath, 0777);
			}
			
			//NOTE: We could use the logger FP as a directory also and then it will create auto date filename logs. (just pass directory instead of file below)
			if(is_writable($logFilePath)) {
				try {
					$this->logger = new \Libsyn\Service\Logger($logFilePath);
				} catch (Exception $e) {
					$this->logger = false;
					$this->loggerFP = false;
				}
			} else {
				$this->logger = false;
				$this->loggerFP = false;
			}
		} else {
			$this->logger = false;
			$this->loggerFP = false;
		}
	}
		
	/**
	 * Create and define the fields of a Base class
	 * 
	 * Sets up an instance of the 'Base' class, which can be iterated over and treated like
	 * an associate array, or used like a Java-style class with automatically generated
	 * getters and setters.  This class also provides a quick and easy way of exporting it's
	 * data to an array and even suppressing certain properties from those exports.
	 *
	 * @see $__ignore
	 * @see $__map
	 * @uses __import()
	 * @uses __map()
	 * @return null
	 * @param array $data Associative array of data mapped to the class properties to import
	 */
	public function __construct($data = array()) {
		$this->__map();
		foreach($data as $key => $value){
			$this->{$key} = $value;
		}
	}

	/**
	 * Map Object Properties
	 *
	 * Creates a map of the object's properties which can be used during iteration and
	 * when the object is exported to an array.
	 */
	public function __map() {
		$className = get_class($this);

		if (isset(self::$__lookups[$className])) {
			$this->__map = self::$__lookups[$className];
		} else {
			$this->__map = array_keys( get_object_vars($this) );

			// Filter out prefixed _ properties, they are not part of the
			// direct object export.  Also use the ignore list to make
			// the mapping a little more flexible.
			foreach ($this->__map as $key) {
				if (substr($key, 0, 1) == '_' || in_array($key, $this->__ignore)) {
					unset($this->__map[ array_search($key, $this->__map) ]);
				}
			}
			self::$__lookups[$className] = $this->__map;
		}
	}

	/**
	 * Import an array of data into an object
	 *
	 * Takes an array of data keyed by property name and imports that data into the object.
	 * This method respects only those properties which exist in the class definition, so if
	 * you try to set a property that doesn't exist on import it will not work.  Additionally
	 * if you have a property '_name' and you import 'name' it will import that too, this is
	 * to allow for '_private' variable naming and still have those properties be importable.
	 *
	 * @param array $data Associative array of data mapped to the class properties to import
	 * @return null
	 */
	protected function __import($data = array()) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			} else if (property_exists($this, '_' . $key)) {
				$k = '_' . $key;
				$this->$k = $value;
			}
		}
	}

	/**
	 * Translate camel case variable names to _ styled names
	 *
	 * This method will take a variable like 'mySpecialVar' and translate it to 'my_special_var'.
	 * This is especially useful with classes that represent database information, because you
	 * can import the result directly from the DB and then reference the field by 
	 * $obj->getMySpecialVar() .
	 *
	 * @param string $var The variable name in camel case form
	 * @return string The variable name in underscore form
	 */
	protected function __fromCamelCase($var) {
		$className = get_class($this);
		if (isset(self::$__lookups[$className])) {
		   	if (isset(self::$__lookups[$className][$var])) {
				return self::$__lookups[$className][$var];
			}
		}
		else {
			self::$__lookups[$className] = array();
		}
		
		$var[0] = strtolower($var[0]);

		$len = strlen($var);
		$new = '';

		for ($i = 0; $i < $len; $i++) {
			if (ord($var[$i]) > 64 && ord($var[$i]) < 91) {
				$new .= '_' . strtolower($var[$i]);
		    } else {
				$new .= $var[$i];
			}
		}

		self::$__lookups[$className][$var] = $new;

		return $new;
	}

	/**
	 * Convert a variable with _ breaks to camel case
	 *
	 * @param string $var The variable name in underscore form
	 * @return string The variable name in camel case form
	 */
	protected function __toCamelCase($key) {
		$className = get_class($this);
		if (isset(self::$__lookups[$className])) {
		   	if (isset(self::$__lookups[$className][$key])) {
				return self::$__lookups[$className][$key];
			}
		}
		else {
			self::$__lookups[$className] = array();
		}

		$var = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
		$var[0] = strtolower($var[0]);

		self::$__lookups[$className][$key] = $var;

		return $var;
	}

	/**
	 * Translates an array offset to a getter and makes sure it's not null
	 *
	 * This method is used by the ArrayAccess interface and allows us to have $obj['property']
	 * translate to $obj->getProperty() and validate that it's not null.
	 *
	 * @param string $key A property name to check if it exists
	 * @return boolean True if the value exists, false if it does not
	 */
	public function offsetExists($key) {
		return (isset($this->$key));

		$var = $this->__toCamelCase($key);
		$var[0] = strtoupper($var[0]);

		$getter = "get" . $var;
		
		return ($this->$getter() != null) ? true : false;
	}

	/**
	 * Translates an array offset to a getter and get its value
	 *
	 * This method is used by the ArrayAccess interface and allows us to have $obj['property']
	 * translate to $obj->getProperty() and get the value.
	 *
	 * @param string $key The property name to translate and fetch the value for
	 * @return mixed The value of that property
	 */
	public function offsetGet($key) {
		$var = $this->__toCamelCase($key);
		$var[0] = strtoupper($var[0]);

		$getter = "get" . $var;

		return $this->$getter();
	}

	/**
	 * Translates an array offset to a setter and set its value
	 *
	 * This method is used by the ArrayAccess interface and allows us to have 
	 * $obj['property'] = $val;  translate to $obj->setProperty($val) and set the value.
	 *
	 * @param string $key The property name to translate and set the value for
	 * @param mixed $value The value to set the property to
	 * @return mixed However the setter is defined to return, if {@link __call()} then {@link $this}
	 */
	public function offsetSet($key, $value) {
		$setter = "set" . ucfirst($key);
		return $this->$setter($value);
	}

	/**
	 * Translates an array offset and destroys the property value
	 *
	 * This method is used by the ArrayAccess interface and allows us to have 
	 * unset($obj['property']) and  translate to $obj->setProperty(null) basically
	 * destroying the value and causing isset($obj['property']) to return false
	 *
	 * @param string $key The property name to translate and unset
	 * @return mixed However the setter is defined to return, if {@link __call()} then {@link $this}
	 */
	public function offsetUnset($key) {
		$setter = "set" . ucfirst($key);
		return $this->$setter(null);
	}

	/**
	 * Reset/rewind the iterator cursor on the object
	 *
	 * Resets/rewinds the position of the array to the begining of the array, this effects 
	 * iteration like it would any other array.
	 */
	public function rewind() {
		reset($this->__map);
	}

	/**
	 * Returns the value of the current key by translating the key to it's getter
	 *
	 * Translates the key to the getter by calling {@link offsetGet()} and then returns the
	 * value of that method.  The current key is the position of the array cursor during 
	 * iteration.
	 */
	public function current() {
		$key = current($this->__map);
		return $this->offsetGet($key);
	}

	/**
	 * Returns the current key
	 *
	 * Returns the key of the current cursor position of the iteration of this object.
	 */
	public function key() {
		return current($this->__map);
	}

	/**
	 * Advanced the cursor position to the next key
	 *
	 * Moves the cursor position to the next key in the iteration of this object.
	 */
	public function next() {
		if (($key = next($this->__map)) === false) {
			return false;
		} else {
			return $this->offsetGet($key);
		}
	}

	/**
	 * Tests that the current key is valid
	 *
	 * Checks if the current cursor position is a legitimate key, or if the cursor has been
	 * moved outside the scope of the iteration of this object.
	 */
	public function valid() {
		return (current($this->__map) !== false);
	}

	/**
	 * Count the number of properties
	 *
	 * Counts the total number of properties available through the iteration of this object.
	 */
	public function count() {
		return count($this->__map);
	}

	/**
	 * String representation of this object
	 *
	 * @return string String representation of this object
	 */
	public function toString() {
		return $this->__toString();
	}

	/**
	 * String representation of this object
	 *
	 * @return string String representation of this object
	 */
	public function __toString() {
		return '';
	}
	
    /**
     * Grabs the minimum php version
     * 
     * 
     * @return <float>
     */
	public function getMinimumPhpVersion() {
		return $this->minimum_php_version;
	}
	
    /**
     * Grabs the maximum tested php version
     * 
     * 
     * @return <float>
     */
	public function getMaxPhpVersion() {
		return $this->max_php_version;
	}
	
    /**
     * Grabs the Admin URL (default: admin-ajax.php)
     * 
     * @param <string> $endpoint 
     * 
     * @return <string>
     */
	public function admin_url($endpoint='') {
		if(empty($endpoint)) {
			return admin_url( 'admin-ajax.php');
		} else {
			return admin_url($endpoint);
		}
	}

	/**
	 * Dynamic Getter/Setter workhorse
	 *
	 * This method is responsible for dynamically handling getter and setter method calls.  The
	 * goal here is to prevent us from having to create getter and setter functions for every
	 * method in our class.  Rather, this method will allow us to take $this->property and do
	 * $this->getProperty() without creating that function, and likewise $this->setProperty($val)
	 * without creating that method.  Furthermore, this method uses {@link __fromCamelCase()} to
	 * provide us with property translation from internal underscore style, ie. $this->my_property
	 * to a camel case getter, ie. $this->getMyProperty()  Note that if you do go ahead and
	 * create a $this->getMyProperty() method this dynamic method will never be called,
	 * allowing us to essentially override the base getters and setters available from
	 * the class.
	 *
	 * For setters and failed properties, ie $this->getPropertyDoesntExist() this method will
	 * return $this, allowing for the class and it's setters to be chainable, ie.
	 * $obj->setProperty($val)->setAnotherProperty($val)->doSomething();
	 *
	 * @param string $method The name of the method being called
	 * @param array $args The arguments passed to the method being called
	 * @return mixed The value being returned by the dynamically generated method.
	 */
	public function __call($method , $args) {
		$property = strtolower( substr($method, 3, 1) ) . substr($method, 4);

		if (empty($property)) {
			return $this;
		}

		if ( substr($method, 0, 3) === "get" && property_exists($this, $property) ) {
			return $this->$property;
		}

		if ( substr($method, 0, 3) === "set" && property_exists($this, $property) ) {
			$this->$property = $args[0];
			return $this;
		}
		
		$_property = $this->__fromCamelCase($property);

		if ( substr($method, 0, 3) === "get" && property_exists($this, $_property) ) {
			return $this->$_property;
		}

		if ( substr($method, 0, 3) === "set" && property_exists($this, $_property) ) {
			$this->$_property = $args[0];
			return $this;
		}
	}	
	
}

?>
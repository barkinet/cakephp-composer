<?php
class ComposerShell extends AppShell {
	public $pharDir;
	
	/**
	 * Initialize ComposerShell
	 * also checks if Composer is installed and offer auto installation option.
	 */
	public function initialize() {
		if (empty($this->pharDir)) {
			if (Configure::read('Composer.phar_dir') !== null) $this->pharDir = Configure::read('Composer.phar_dir');
			else $this->pharDir = dirname(dirname(dirname(__FILE__))).DS.'Vendor'.DS.'Composer'.DS;
		}
		
		/**
		 * Check if composer.phar is available
		 */
		$version = @exec("php {$this->pharDir}composer.phar --version");
		
		if (stripos($version, 'Composer') === false || stripos($version, 'version') === false) {
			$this->out('<warning>Composer is not installed.</warning>');
			$setup = $this->in('Would you like Composer to be set up automatically?', array('y', 'n'), 'y');
			
			if ($setup != 'y') {
				$this->error("Terminating. You may overwrite the location of composer.phar by defining 'Composer.phar_dir' configuration.");
			}
			else {
				$this->setup();
			}
		}
		
		$this->_checkComposerJSON();
	}
	
	public function startup() {
		$this->out("<info>Composer plugin for CakePHP</info> by U-Zyn Chua.", 2);
	}
	
	public function main() {
		passthru("php {$this->pharDir}composer.phar ".implode(" ", $this->args)." ".self::_optionsToString($this->params));
	}
	
	/**
	 * Grabs the latest composer.phar from http://getcomposer.org/composer.phar
	 * Changeable at CakePHP configuration: Composer.phar_url
	 */
	public function setup() {
		$pharURL = 'http://getcomposer.org/composer.phar';
		if (Configure::read('Composer.phar_url') !== null) $pharURL = Configure::read('Composer.phar_url');
		
		if (!is_writable($this->pharDir)) {
			$this->error("$this->pharDir is not writable.");
		}
		
		$this->out('<info>Setting up Composer</info>');
		$this->out("Downloading composer.phar from $pharURL...");
		
		$content = file_get_contents($pharURL);
		if ($content === false) {
			$this->error("Download failed");
		}
		
		$save = file_put_contents($this->pharDir.'composer.phar', $content);
		
		if ($save === false) {
			$this->error("Unable to save to {$this->pharDir}composer.phar.");
		}
		
		$this->out("<info>Composer installed and saved successfully.</info>");
	}
	
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		
		/**
		 * Listing options from Composer
		 * or CakePHP's Shell will exit upon unrecognized options.
		 */
		$parser->addOptions(array(
			'help' => array('short' => 'h'),
			'quiet' => array('short' => 'q'),
			'verbose' => array('short' => 'v'),
			'version' => array('short' => 'V'),
			'ansi' => array(),
			'no-ansi' => array(),
			'no-interaction' => array('short' => 'n')
		));
		
		return $parser;
	}
	
	/**
	 * Convert options to string
	 * 
	 * @param array $options Options array
	 * @return string Results
	 */
	private static function _optionsToString($options) {
		if (empty($options) || !is_array($options)) return '';
		
		$results = '';
		foreach ($options as $option => $value) {
			if (strlen($results) > 0) $results .= ' ';
			if (empty($value)) $results .= "--$option";
			else $results .= "--$option=$value";
		}
		
		return $results;
	}
	
	/**
	 * Determine that composer.json is configured properly.
	 * Checks that vendor-dir is set, defaults to APP.Vendor if it isn't.
	 * Does not overwrite if vendor-dir has been set explicitly.
	 */
	private function _checkComposerJSON(){
		if (file_exists('composer.json')) $jsonLocation = 'composer.json';
		else $jsonLocation = APP.'composer.json';
		
		$jsonSave = false;
		if (file_exists($jsonLocation)) {
			$json = json_decode(file_get_contents($jsonLocation));
			
			if (empty($json)) {
				$this->out('<warning>Your composer.json is not valid.</warning>');
				$create = $this->in('Overwrite the existing and create a default pre-configured composer.json?', array('y', 'n'), 'y');
				
				if ($create == 'y'){
					$json = new stdClass;
					$json->config->{'vendor-dir'} = 'Vendor';
					$jsonSave = true;
				}
			}
			
			if (empty($json->config->{'vendor-dir'})) {
				$json->config->{'vendor-dir'} = 'Vendor';
				$jsonSave = true;
			}
		}
		else{
			$json = new stdClass;
			$json->config->{'vendor-dir'} = 'Vendor';
			$jsonSave = true;
		}
		
		if ($jsonSave) {
			file_put_contents($jsonLocation, json_encode($json));
		}
	}
}
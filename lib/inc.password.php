<?php

//require_once SGISBASE.'/externals/password-lib/lib/PasswordLib/PasswordLib.php';

global $pwObj;
//$pwObj = new PasswordLib\PasswordLib();

/**
 * replacement for external password lib
 * current php versions of php implement own password handling
 * old mediawiki logins are not required anymore
 */
class ownPasswordLib {
	/**
	 * contains the own object
	 * class implements singleton design pattern
	 * @var Router::instance
	 */
	protected static $_instance = null;
	
	
	// ================================================================================================
	
	/**
	 * private class constructor
	 * implements singleton pattern
	 */
	protected function __construct(){
	}
	/**
	 * returns instance of this class
	 * implements singleton pattern
	 * @return Router
	 */
	public static function getInstance()
	{
		if (!isset(static::$_instance)) {
			self::$_instance = new ownPasswordLib();
		}
		return static::$_instance;
	}
	
	/**
	 * prevent cloning of an instance via the clone operator
	 */
	protected function __clone() {}
	
	/**
	 * prevent unserializing via the global function unserialize()
	 *
	 * @throws \Exception
	 */
	public function __wakeup()
	{
		throw new \Exception("Cannot unserialize singleton");
	}
	
	// ================================================================================================
	
	/**
	 * hashes password with best password algorithem and return data
	 * if argon2 is available this will be used, if not, bcrypt will be used
	 * @param string $password
	 * @return string
	 */
	public static function hashPassword($password){
		if (defined('PASSWORD_ARGON2I')){
			return self::hashPasswordArgon2($password);
		} else {
			return self::hashPasswordBcrypt($password);
		}
	}
	
	/**
	 * hashes password with argon2 algorithm
	 * @param string $password
	 * @return string
	 */
	public static function hashPasswordArgon2($password){
		return password_hash($password, PASSWORD_ARGON2I);
	}
	
	/**
	 * hashes password with bcrypt algorithm
	 * @param string $password
	 * @return string
	 */
	public static function hashPasswordBcrypt($password){
		$options = [
		    'cost' => 12,
		];
		return password_hash($password , PASSWORD_BCRYPT, $options);
	}
	
	/**
	 * check if provided password matches given hash value
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	public static function verifyPassword($password, $hash){
		return password_verify ($password, $hash);
	}
	
	// ================================================================================================
	
	/**
	 * function wrapper for old PasswordLib
	 * @param string $password
	 * @return string
	 * @see hashPassword
	 */
	public function createPasswordHash($password){
		return $this->hashPassword($password);
	}
	
	/**
	 * function wrapper for old PasswordLib
	 * @param string $password
	 * @return string
	 * @see verifyPassword
	 */
	public function verifyPasswordHash($password, $hash, $person_id = null){
		$result = $this->verifyPassword($password, $hash);
		// check old password lib, and opionally update password
		if (!$result && defined('CORE_SUPPORT_OLD_PASSWORDS') && CORE_SUPPORT_OLD_PASSWORDS){
			//load lib
			require_once SGISBASE.'/externals/password-lib/lib/PasswordLib/PasswordLib.php';
			$oldPwObj = new \PasswordLib\PasswordLib();
			//verify password
			if (@$oldPwObj->verifyPasswordHash($password, $hash)){
				$result = true;
				if ( defined('CORE_SUPPORT_TRY_GLOBAL_PASSWORD_PERSON') && CORE_SUPPORT_TRY_GLOBAL_PASSWORD_PERSON && !isset($person_id) || !empty($person_id)){
					global $person;
					if (isset($person) && isset($person['id']) && !empty($person['id'])){
						$person_id = $person['id'];
					}
				}
				// update old to new password type
				if (isset($password) && !empty($password) &&
					isset($person_id) && !empty($person_id)){
					$nh = $this->createPasswordHash($password);
					if (isset($nh) && !empty($nh) ){
						//store to database
						global $pdo, $DB_PREFIX;
						$query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET password = ? WHERE id = ?");
						if (!$query->execute(Array($nh, $person_id)) ){ 
							httperror(print_r($query->errorInfo(),true));
						}
					}
				}
			}
		}
		return $result;
	}
};

$pwObj = \ownPasswordLib::getInstance();



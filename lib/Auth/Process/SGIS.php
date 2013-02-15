<?php

/**
 * Add SGIS attributes into reply.
 *
 * @package stura-sgis
 * @version $Id$
 */
class sspmod_sgis_Auth_Process_SGIS extends SimpleSAML_Auth_ProcessingFilter {

        /**
        * The database object.
         *
         * @var PDO
         */
        private $pdo;

        /**
        * The database prefix.
         *
         * @var prefix
         */
        private $prefix;

        /**
         * Initialize this filter, parse configuration
         *
         * @param array $config  Configuration information about this filter.
         * @param mixed $reserved  For future use.
         */
        public function __construct($config, $reserved) {
                parent::__construct($config, $reserved);

                assert('is_array($config)');

                if (!isset($config['dsn'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'dsn\' option.');
                }
                if (!isset($config['username'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'username\' option.');
                }
                if (!isset($config['password'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'password\' option.');
                }
                if (!isset($config['prefix'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'prefix\' option.');
                }

                $this->pdo = new PDO((string) $config["dsn"], (string) $config["username"], (string) $config["password"]);
		$this->prefix = $config['prefix'];
		$this->config = $config;
        }


        /**
         * Apply the SGIS transformation
         *
         * @param array &$request  The current request
         */
        public function process(&$request) {
                assert('is_array($request)');
                assert('array_key_exists("Attributes", $request)');

                $attributes = &$request['Attributes'];
                $mail = $attributes["mail"][0];
                $unirzlogin = $attributes["eduPersonPrincipalName"][0];
		$prefix = $this->prefix;
                
                $query = $this->pdo->prepare("SELECT id, name, username, canLogin, unirzlogin FROM {$prefix}person p WHERE p.unirzlogin = ?");
                $query->execute(array($unirzlogin));
                $user = $query->fetchAll(PDO::FETCH_ASSOC);
                $valid = false;
                if (count($user) > 0) {
                  $user = $user[0];
                  $valid = true;
                } else { // new user
                  $query = $this->pdo->prepare("SELECT id, name, username, canLogin, unirzlogin FROM {$prefix}person p WHERE p.email = ?");
                  $query->execute(array($mail));
                  $user = $query->fetchAll(PDO::FETCH_ASSOC);
                  if (count($user) > 0) {
                    $user = $user[0];
                    if (empty($user["unirzlogin"])) {
                      $query = $this->pdo->prepare("UPDATE {$prefix}person SET unirzlogin = ? WHERE id = ?");
                      $query->execute(Array($unirzlogin, $user["id"]));
                      $valid = true;
                    }
                  }
                }
                if ($valid) {
                  $query = $this->pdo->prepare("SELECT g.name FROM {$prefix}gruppe g INNER JOIN {$prefix}rel_rolle_gruppe rrg ON g.id = rrg.gruppe_id INNER JOIN {$prefix}rel_mitgliedschaft rrm ON rrg.rolle_id = rrm.rolle_id AND (rrm.von IS NULL OR rrm.von <= CURRENT_DATE) AND (rrm.bis IS NULL OR rrm.bis >= CURRENT_DATE) WHERE rrm.person_id = ?");
                  $query->execute(array($user["id"]));
                  $grps = $query->fetchAll( PDO::FETCH_COLUMN, 0 );
                  $grps[] = "sgis";
                  $grps[] = "user";
                  $valid = (bool) $user["canLogin"];
                  $valid = (($valid && !in_array("cannotLogin",$grps)) || (!$valid && in_array("canLogin",$grps)));
                }
                if ($valid) {
                  if (!empty($user["username"])) {
                    $attributes["eduPersonPrincipalName"] = Array($user["username"]);
                  }
                  if (!empty($user["name"])) {
                    $attributes["displayName"] = Array($user["name"]);
                  }
                  $query = $this->pdo->prepare("UPDATE {$prefix}person SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
                  $query->execute(Array($user["id"]));
                  $attributes["groups"] = array_unique(array_merge($attributes["groups"], $grps));
                  $query = $this->pdo->prepare("SELECT DISTINCT m.address FROM {$prefix}mailingliste m INNER JOIN {$prefix}rel_rolle_mailingliste rrm ON m.id = rrm.mailingliste_id INNER JOIN {$prefix}rel_mitgliedschaft rm ON rrm.rolle_id = rm.rolle_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) WHERE rm.person_id = ?");
		  $query->execute(Array($user["id"]));
                  $mailinglists = $query->fetchAll( PDO::FETCH_COLUMN, 0 );
                  $attributes["mailinglists"] = array_unique($mailinglists);
                }
                if (!isset($attributes["displayName"])) {
                  $attributes["displayName"] = $attributes["eduPersonPrincipalName"];
                }
		# if sgis user and no username/password is set, we ask the user to do it now
		if ($valid && empty($user["username"]) && (! (isset($request['isPassive']) && $request['isPassive'] == true))) {
			// Save state and redirect
			$request['sgis:person_id'] = $user["id"];
			$request['sgis:config'] = $this->config;
			$id  = SimpleSAML_Auth_State::saveState($request, 'sgis:requestusernamepassword');
			$url = SimpleSAML_Module::getModuleURL('sgis/getusernamepassword.php');
			SimpleSAML_Utilities::redirect($url, array('StateId' => $id));
		}

        }

}


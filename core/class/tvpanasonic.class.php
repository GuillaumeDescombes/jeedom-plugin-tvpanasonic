<?php

/*
 * This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ***************************** Includes ******************************
 */
require_once dirname ( __FILE__ ) . '/../../../../core/php/core.inc.php';
class tvpanasonic extends eqLogic {
	/**
	 * ***************************** Attributs ******************************
	 */
	/* Ajouter ici toutes vos variables propre à votre classe */
	
	/**
	 * *************************** Methode static ***************************
	 */
	/* methode de test à améliorer */
	
	
	// Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {
		foreach (self::byType('tvpanasonic') as $tvpanasonic) {
			if ($tvpanasonic->getIsEnable() == 1) {
				$tvpanasonic->refreshStatus();
			}
		}
	}
	
	/*
	 * // Fonction exécutée automatiquement toutes les heures par Jeedom
	 * public static function cronHourly() {
	 *
	 * }
	 */
	
	/*
	 * // Fonction exécutée automatiquement tous les jours par Jeedom
	 * public static function cronDayly() {
	 *
	 * }
	 */
	
	/**
	 * ************************* Methode d'instance *************************
	 */
	
	/**
	 * ************************ Pile de mise à jour *************************
	 */
	
	/* fonction appelé avant le début de la séquence de sauvegarde */
	public function preSave() {
	}
	
	/*
	 * fonction appelé pendant la séquence de sauvegarde avant l'insertion
	 * dans la base de données pour une mise à jour d'une entrée
	 */
	public function preUpdate() {
		if ($this->getConfiguration ( 'ipTvPanasonic' ) == '') {
			throw new Exception ( __ ( 'Le champs "Adresse IP TV Panasonic" ne peut etre vide', __FILE__ ) );
		}
	}
	
	/*
	 * fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une mise à jour d'une entrée
	 */
	public function postUpdate() {
	}
	
	/*
	 * fonction appelé pendant la séquence de sauvegarde avant l'insertion
	 * dans la base de données pour une nouvelle entrée
	 */
	public function preInsert() {
		//$this->setCategory ( 'multimedia', 1 );
	}
	
	/*
	 * fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une nouvelle entrée
	 */
	public function postInsert() {
		$onoff_state = $this->getCmd(null, 'onoff_state');
        if(!is_object($onoff_state)) {
            $onoff_state = new tvpanasonicCmd();
            $onoff_state->setLogicalId('onoff_state');
            $onoff_state->setIsVisible(1);
            $onoff_state->setName('Etat');
            $onoff_state->setIsVisible(0);
            $onoff_state->setEqLogic_id($this->getId());
            $onoff_state->setDisplay('generic_type', 'ENERGY_STATE');
        }
        $onoff_state->setType('info');
        $onoff_state->setSubType('binary');
		$onoff_state->save();

        $on = $this->getCmd(null, 'on');
        if(!is_object($on)) {
            $on = new tvpanasonicCmd();
            $on->setLogicalId('on');
            $on->setName('On');
            $on->setIsVisible(1);
            $on->setEqLogic_id($this->getId());
            $on->setType('action');
            $on->setSubType('other');
            $on->setDisplay('generic_type', 'ENERGY_ON');
            $on->setTemplate('dashboard', 'prise');
            $on->setTemplate('mobile', 'prise');
            $on->setValue($onoff_state->getId());
            $on->setConfiguration('updateCmdId', $onoff_state->getEqLogic_id());
            $on->setConfiguration('updateCmdToValue', 1);
            $on->save();
        }
        $off = $this->getCmd(null, 'off');
        if(!is_object($off)) {
            $off = new tvpanasonicCmd();
            $off->setLogicalId('off');
            $off->setName('Off');
            $off->setIsVisible(1);
            $off->setEqLogic_id($this->getId());
            $off->setType('action');
            $off->setSubType('other');
            $off->setDisplay('generic_type', 'ENERGY_OFF');
            $off->setTemplate('dashboard', 'prise');
            $off->setTemplate('mobile', 'prise');
            $off->setConfiguration('updateCmdId', $onoff_state->getEqLogic_id());
            $off->setConfiguration('updateCmdToValue', 0);
            $off->setValue($onoff_state->getId());
            $off->save();
        }

        $refresh = $this->getCmd(null, 'refresh');
        if(!is_object($refresh)) {
            $refresh = new tvpanasonicCmd();
            $refresh->setLogicalId('refresh');
            $refresh->setIsVisible(1);
            $refresh->setName('Rafraîchir');
            $refresh->setEqLogic_id($this->getId());
            $refresh->setType('action');
            $refresh->setSubType('other');
            $refresh->save();
        }
	}
	
	/* fonction appelé après la fin de la séquence de sauvegarde */
	public function postSave() {		
		
	}
	
	/* fonction appelé avant l'effacement d'une entrée */
	public function preRemove() {
	}
	
	/* fonnction appelé après l'effacement d'une entrée */
	public function postRemove() {
	}
	
	public function refreshStatus(){
		$ip = $this->getConfiguration('ipTvPanasonic');	
		log::add( 'tvpanasonic', 'info', 'refreshStatus start avec l\'ip '.$ip );
		if($ip != '') {
            $curl = curl_init();
            $post = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">\r\n <s:Body>\r\n  <u:GetVolume xmlns:u=\"urn:schemas-upnp-org:service:RenderingControl:1\">\r\n   <InstanceID>0</InstanceID>\r\n   <Channel>Master</Channel>\r\n   <DesiredVolume></DesiredVolume>\r\n  </u:GetVolume>\r\n </s:Body>\r\n</s:Envelope>";
            curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://".$ip.":55000/dmr/control_0",
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_TIMEOUT => 2,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $post,
                    CURLOPT_HTTPHEADER => array(
                            "content-type: text/xml",
                            "content-length: ".$post.length,
                            "soapaction: \"urn:schemas-upnp-org:service:RenderingControl:1#GetVolume\""
                    )
            ));
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            $etatTv = $this->getCmd(null, 'onoff_state');
            log::add( 'tvpanasonic', 'info', 'refreshStatus info : '.$httpcode.' et '.print_r($response));
            if ($httpcode>=200 && $httpcode<300) {
                if (is_object($etatTv) && $etatTv->formatValue(1) !== $etatTv->execCmd(null, 2)) {
                     $etatTv->setCollectDate('');
                     $etatTv->event(1);
                }
             } else {
                 if (is_object($etatTv) && $etatTv->formatValue(0) !== $etatTv->execCmd(null, 2)) {
                     $etatTv->setCollectDate('');
                     $etatTv->event(0);
                 }
             }
             $this->refreshWidget();
        }
	}
	
	public function onMethode() {
		$etatTv = $this->getCmd(null, 'onoff_state');
		if (is_object($etatTv) && $etatTv->formatValue(0) === $etatTv->execCmd(null, 2)) {
			$this->onOffMethode();
		}
	}
	
	public function offMethode() {
		$etatTv = $this->getCmd(null, 'onoff_state');
		if (is_object($etatTv) && $etatTv->formatValue(1) === $etatTv->execCmd(null, 2)) {
			$this->onOffMethode();
		}
	}
	
	public function onOffMethode() {
		$ip = $this->getConfiguration('ipTvPanasonic');
		log::add( 'tvpanasonic', 'info', 'OnOffMethode ' . $ip );
		if ($ip != '') {				
			$curl = curl_init();
			$post = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">\r\n <s:Body>\r\n  <u:X_SendKey xmlns:u=\"urn:panasonic-com:service:p00NetworkControl:1\">\r\n   <X_KeyEvent>NRC_POWER-ONOFF</X_KeyEvent>\r\n  </u:X_SendKey>\r\n </s:Body>\r\n</s:Envelope>";
			curl_setopt_array($curl, array(
					CURLOPT_URL => "http://".$ip.":55000/nrc/control_0",
					CURLOPT_TIMEOUT => 2,
                    CURLOPT_CONNECTTIMEOUT => 2,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $post,
					CURLOPT_HTTPHEADER => array(
							"content-type: text/xml",
							"content-length: ".$post.length,
							"soapaction: \"urn:panasonic-com:service:p00NetworkControl:1#X_SendKey\""
					)
			));
			$response = curl_exec($curl);
			curl_close($curl);
			log::add( 'tvpanasonic', 'info', 'onOffMethode reponse : '.print_r($response));
		}
	}
	
	/*
	 * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	 * public function toHtml($_version = 'dashboard') {
	 *
	 * }
	 */
	
	/* * **********************Getteur Setteur*************************** */
}
class tvpanasonicCmd extends cmd {
	/**
	 * ***************************** Attributs ******************************
	 */
	/* Ajouter ici toutes vos variables propre à votre classe */
	
	/**
	 * *************************** Methode static ***************************
	 */
	
	/**
	 * ************************* Methode d'instance *************************
	 */
	
	/*
	 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	 * public function dontRemoveCmd() {
	 * return true;
	 * }
	 */
	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId () == 'on') {
			$eqLogic->onMethode();
			sleep(2);
			$eqLogic->refreshStatus();
		}
		if ($this->getLogicalId () == 'off') {
			$eqLogic->offMethode();
			sleep(2);
			$eqLogic->refreshStatus();
		}
		if ($this->getLogicalId() == 'refresh') {
			$eqLogic->refreshStatus();
		}
	}

/**
 * *************************** Getteur/Setteur **************************
 */
}

?>
<?php

/* This file is part of Jeedom.
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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'tvpanasonic')) {
  echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
  log::add('tvpanasonic', 'info', "Callback - wrong API key");
  die();
}
if (init('test') != '') {
  echo 'OK';
  die();
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
  die();
}

log::add('tvpanasonic', 'debug',"Callback call from daemon...");
$found=false;
if (isset($result['daemon'])) {
  // message from daemon
  
  //event
  if (isset($result['daemon']['event'])) {
    if ($result['daemon']['event']!="Ping") log::add('tvpanasonic', 'debug', "Callback - message from daemon: event = '" . $result['daemon']['event'] . "'");
    if ($result['daemon']['event']=='Listening') {
      // register all the eqLogics
      log::add('tvpanasonic', 'debug', "Register all the eqlogics...");
      $eqLogics = eqLogic::byType('tvpanasonic');
      foreach ($eqLogics as $eqLogic) {
          $ip = $eqLogic->getConfiguration('ipTvPanasonic');
          $name = $eqlogic->getId();
          log::add('tvpanasonic', 'info', "Register  " . $name ." in deamon");
          $device=array('name' => $name, 'ip' => $ip, 'appId' => '', 'encryptionKey' => '');
          $eqLogic::request('register', $device);
      }
    }
  } else {
      //other message
      log::add('tvpanasonic', 'info', "Callback - message from daemon: " . print_r($result['daemon'], true));
    }
  $found=true;
}

if (isset($result['devices'])) {
  // message from device
  foreach ($result['devices'] as $name => $value) {
    log::add('tvpanasonic', 'debug', "Callback - device '" . $name . "' => " . print_r($value, true));
  }
  $found=true;
}

if (isset($result['infos'])) {
  foreach ($result['infos'] as $device => $value) {
    log::add('tvpanasonic', 'info', "Callback - infos for device '" . $device . "': '" . print_r($value,true) ."'");
  }
  $found=true;
}

if (!$found) {
  //other
  log::add('tvpanasonic', 'info', "Callback - " . print_r($result, true));
}


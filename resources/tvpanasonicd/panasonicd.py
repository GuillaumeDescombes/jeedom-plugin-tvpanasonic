# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import string
import sys
import os
import time
import datetime
import argparse
import binascii
import re
import signal
import traceback
from optparse import OptionParser
from os.path import join
import json
import logging
from xml.dom.minidom import parseString
from typing import Any, List, Mapping, Optional, Union
from inspect import signature
from enum import Enum
import operator
import panasonic_viera

try:
    from jeedom.jeedom import *
except ImportError:
    logging.error("Error: importing module from jeedom folder")
    sys.exit(1)

# Simple device control
class devices:
    """ A simple class with a register and unregister methods
    """

    def __init__(self, cycle:float, debug:bool):
        self.devices = {}
        self.debug = debug
        self.notifyCmd = None
        self.notifyEvent = None
        self.shutDown = False
        self.cycle = cycle
        self.lastCmd = 0

    def register(self, info):
        if "name" in info and ("ip" in info or "host" in info):
            name = info["name"].lower()
            if "host" in info:
                host = info["host"]
            else:
                host = info["ip"]
            if name not in self.devices:
                logging.info(f"Registering '{name}' - '{host}' in device list")
                jeedomCom.add_changes(f"devices::{name}::event", {'name': name, 'value' : 'register'});
                appId=None
                if "appId" in info:
                    appId = info["appId"]
                encryptionKey=None
                if "encryptionKey" in info:
                    encryptionKey = info["encryptionKey"]
                device = panasonic_viera.RemoteControl(host, app_id=appId, encryption_key=encryptionKey)
                self.devices[name] = device

    def unregister(self, name:str):
        name=name.lower()
        if name in self.devices:
            logging.info(f"Unregistering 'name' in device list")
            logging.debug(f"'{name}' is gone")
            del self.devices[name]
            #send event to jeedom
            jeedomCom.add_changes(f"devices::{name}::event", {'name': name, 'value' : 'unregister'});

    def unregisterAll(self):
        for name in self.devices:
            self.unregister(name)

    def doAction(self, name:str, action:str, value: Any):
        name=name.lower()
        if name in self.devices:
            logging.info(f"Executing '{action}' for device '{name}'")
            device = self.devices[name]
            try:
                func = getattr(device, f"do{action}")
                sig = signature(func)
                params = sig.parameters
                logging.debug("found a function with signature '"+str(sig)+"'")
                if "value" in params and len(params) == 1:
                    logging.info(f"--> {action}({value})")
                    func(value)
                elif len(params) == 0:
                    logging.info(f"--> {action}()")
                    func()
                else:
                    logging.info(f"--> function {action} not found")
            except AttributeError as e:
                logging.info(f"function do{action} does not exist")

    def notificationCmd(self, device, commandDef, value):
        name = "unknown"
        for key,val in self.devices.items():
          if val == device:
            name=key
            break
        if isinstance(value, Enum):
            valueConv=value.value
        elif isinstance(value, List):
            #check if list of Enum
            if isinstance(value[0], Enum):
                valueConv=[x.value for x in value]
            else:
                valueConv=[x for x in value]
        elif isinstance(value, Mapping):
            #check if mapping of Enum
            if isinstance(list(value.values())[0], Enum):
                #check if key is Enum
                if isinstance(list(value.keys())[0], Enum):
                    valueConv={x.value:value[x].value for x in value}
                else:
                    valueConv={x:value[x].value for x in value}
            else:
                #check if key is Enum
                if isinstance(list(value.keys())[0], Enum):
                    valueConv={x.value:value[x] for x in value}
                else:
                    valueConv={x:value[x] for x in value}
        else:
            valueConv=value
        logging.debug(f"notificationCmd -> {name}: Value for '{commandDef.id}' changed to '{valueConv}'")
        jeedomCom.add_changes(f"devices::{name}::{commandDef.id}", {'name': name, 'cmdId': commandDef.id, 'value': valueConv});
        jeedomCom.add_changes(f"devices::{name}::lastMessageDate", {'name': name, 'value':time.strftime("%d/%m/%Y %H:%M:%S")});

    def notificationEvent(self, device, event, value):
        name = "unknown"
        for key,val in self.devices.items():
          if val == device:
            name=key
            break
        logging.debug(f"notificationEvent -> {name}: Event '{event.value}'")
        jeedomCom.add_changes(f"devices::{name}::event", {'name': name, 'value' : event.value});
        jeedomCom.add_changes(f"devices::{name}::lastMessageDate", {'name': name, 'value':time.strftime("%d/%m/%Y %H:%M:%S")});

    def stop(self):
        self.shutDown=True    
##

def handler(signum=None, frame=None):
    signame = signal.Signals(signum).name
    logging.info(f"Signal {signame} ({signum}) caught, exiting...")
    shutdown()

def shutdown():
    global MyDevices
    global jeedomSocket

    #jeedomCom.add_changes("daemon", {'event' : 'Shutdown'})
    jeedomCom.send_change_immediate({"daemon": {'event' : 'Shutdown'}})
    logging.info("Shutdown")
    try:
        if MyDevices:
            MyDevices.stop()
    except:
        pass

    logging.info("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    logging.info("Closing jeedom Socket ")
    try:
        jeedomSocket.close()
    except:
        pass
    logging.info("Exit 0")
    sys.stdout.flush()
    os._exit(0)

def main():
    global MyDevices
    global jeedomSocket
    global jeedomCom
    global _cycle
    global _cycleConnect
    global _log_level
    global _watchDogTimer
    global JEEDOM_SOCKET_MESSAGE

    MyDevices = devices(_cycleConnect, _log_level=="debug")
    logging.debug("Start listening...")
    jeedomSocket.open()
    jeedomCom.send_change_immediate({"daemon": {'event' : 'Listening'}})
    time.sleep(5)
    listInfo = {"name": "myTV#1", "ip": "192.168.128.182", "appId": "AUkpv2Rdhr+jdA==", "encryptionKey": "6aB7z6fqiKhkia0oTOn3vQ=="}
    MyDevices.register(listInfo)

    cpt=0
    mustQuit=False
    while not mustQuit:
        time.sleep(_cycle)
        try:
            if not JEEDOM_SOCKET_MESSAGE.empty():
                logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
                jsonMessage=JEEDOM_SOCKET_MESSAGE.get()
                logging.debug(f"message {jsonMessage}")
                message = json.loads(jsonMessage)
                if message['apikey'] != _apikey:
                    logging.error("Invalid apikey from socket : " + str(message))
                else: 
                    # do the action
                    # Register/Unregister
                    if message['action'] == "register":
                        newDeviceInfo = {"name":message['name'], "ip":message['ip']}
                        MyDevices.register(newDeviceInfo)
                    if message['action'] == "unregister":
                        MyDevices.unregister(message['name'])
                    if message['action'] == "unregisterAll":
                        MyDevices.unregisterAll() 
                    # action to device
                    if message['action'] == "doDevice":
                        name = message['name']
                        deviceAction = message['deviceAction']
                        value=None
                        if 'value' in message:
                            value=message['value']
                        MyDevices.doAction(name, deviceAction, value)
                    # stop
                    if message['action'] == "quit":
                        mustQuit = True
        except Exception as e:
            logging.error(f'Fatal error: {e}')
            logging.info(traceback.format_exc())
        if (_watchDogTimer > 0 and cpt % round(_watchDogTimer / _cycle) == 0):
            jeedomCom.add_changes("daemon", {'event' : 'Ping'});
            #logging.debug("Still alive")
            cpt = 0
        cpt=cpt+1


# ----------------------------------------------------------------------------

jeedomSocket = None
jeedomCom=None
MyDevices = None

_log_level = "debug"
_socket_port = 55011
_socket_host = '127.0.0.1'
_pidfile = '/tmp/tvpanasonicd.pid'
_apikey = '5Gi6VvwCrFw5ox0PJaEefhwdRgM8CF3ECRTGU1mBNIX77VHmm8LYgJbD7khm5Bcj'
_callback = 'http://192.168.130.2:80/plugins/tvpanasonic/core/php/jeeTV.php'
_cycle = 1
_cycleConnect = 60
_watchDogTimer = 300 # 5min

parser = argparse.ArgumentParser(description='TV Panasonic Daemon for Jeedom plugin')
parser.add_argument("--sockethost", help="Socket host for server", type=str)
parser.add_argument("--socketport", help="Socket port for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--cycleConnect", help="Cycle to connect to device", type=str)
parser.add_argument("--watchDogTimer", help="watch dog time", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
args = parser.parse_args()

if args.sockethost:
    _socket_host = args.sockethost
if args.socketport:
    _socket_port = int(args.socketport)
if args.loglevel:
    _log_level = args.loglevel
_log_level = "debug"  #force debug
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.cycle:
    _cycle = float(args.cycle)
if args.cycleConnect:
    _cycleConnect = float(args.cycleConnect)
if args.watchDogTimer:
    _watchDogTimer = float(args.watchDogTimer)

jeedom_utils.set_log_level(_log_level)

print(f'Start avrd')
logging.info('Log level: '+str(_log_level))
logging.info('PID file: '+str(_pidfile))
logging.info('Apikey: '+str(_apikey))
logging.info('Socket: '+str(_socket_host)+':'+str(_socket_port))
logging.info('Callback: '+str(_callback))
logging.info('Cycle: '+str(_cycle))
logging.info('CycleConnect: '+str(_cycleConnect))
logging.info('WatchdogTimer: '+str(_watchDogTimer))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedomCom = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
    if not jeedomCom.test():
        logging.error('Network communication issues. Please fix your Jeedom network configuration.')
        shutdown()
    jeedomSocket = jeedom_socket(port=_socket_port,address=_socket_host)
    main()
    shutdown()
except Exception as e:
    logging.error('Fatal error: '+str(e))
    logging.info(traceback.format_exc())
    shutdown()

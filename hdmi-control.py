#!/usr/bin/env python

import RPi.GPIO as GPIO
import time
from threading import Timer
import subprocess

GPIO.setmode(GPIO.BCM)
GPIO.setup(21, GPIO.IN)
timer = False
monitor_is_on = True

def monitor_off():
  global monitor_is_on
  #subprocess.call('/opt/vc/bin/vcgencmd display_power 0', shell=True)
  subprocess.call('echo standby 0 | cec-client -s -d 1', shell=True)
 monitor_is_on = False

def monitor_on():
  global monitor_is_on
  #subprocess.call('/opt/vc/bin/vcgencmd display_power 1', shell=True)
  subprocess.call('echo on 0 | cec-client -s -d 1', shell=True)
  monitor_is_on = True


while True:
  time.sleep(0.5)
  movement = GPIO.input(21)
  if movement:
    if timer:
      print "cancel timer"
      timer.cancel()
      timer = False
    if not monitor_is_on:
      print "monitor on"
      monitor_on()
  else:
    if not timer:
      print "start timer"
      timer = Timer(60*5, monitor_off)
      timer.start()


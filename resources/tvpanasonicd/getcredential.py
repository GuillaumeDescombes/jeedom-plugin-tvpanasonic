import panasonic_viera
import logging

logging.basicConfig(level=logging.DEBUG)

rc = panasonic_viera.RemoteControl("192.168.128.182")
# Make the TV display a pairing pin code
rc.request_pin_code()
# Interactively ask the user for the pin code
pin = input("Enter the displayed pin code: ")
# Authorize the pin code with the TV
rc.authorize_pin_code(pincode=pin)
# Display credentials (application ID and encryption key)
print(f"appid: {rc.app_id}")
print(f"key: {rc.enc_key}")


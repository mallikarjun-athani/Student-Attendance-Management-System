<?php

return [
  // Supported providers: 'fast2sms'
  'provider' => 'fast2sms',

  'dev_mode' => true,

  // Fast2SMS config
  // Create an account and generate API key, then set it below.
  // https://www.fast2sms.com/
  'fast2sms_api_key' => 'IVKbCB3TrvfoPQ1kgwMjs8ephl9xnFiE6UR7XzD0maYuOtAW54g4hSqJObP8Ft2jXkLQEeC0yTZocmVw',

  // Message template. {otp} will be replaced.
  'message_template' => 'Your OTP is: {otp}. It will expire in 10 minutes.',

  // Enable debug to get provider response in error messages
  'debug' => false
];

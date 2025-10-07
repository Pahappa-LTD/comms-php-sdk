# Comms platform PHP SDK

Usage:
```php
private $sdk;
$this->sdk = CommsSDK::authenticate('username', 'password');
$this->sdk->sendSMS('+256772123456', 'Test message');
$numbers = ['+256772123456', '0772123457'];
$this->sdk->sendSMS($numbers, 'Test message');
$balance = $this->sdk->getBalance()
```
# CommsSDK PHP SDK

A PHP implementation of the CommsSDK for sending SMS and managing communications, following the same patterns as the Python, Ruby, and other language reference implementations.

**Version:** 1.0.1

---

## Features

- Consistent API across all supported languages
- Authenticate with username and API key
- Send SMS to one or more recipients
- Optional sender ID and message priority
- Check account balance
- Comprehensive error handling

---

## Installation

Install via Composer:

```bash
composer require pahappa/comms-sdk
```

Or add to your `composer.json`:

```json
{
  "require": {
    "pahappa/comms-sdk": "1.0.1"
  }
}
```

---

## Usage

### Basic Authentication

```php
require_once 'vendor/autoload.php';

use CommsSDK\V1\CommsSDK;

// Authenticate with your username and API key
$sdk = CommsSDK::authenticate('your_username', 'your_api_key');
```

### Sending SMS

```php
// Send SMS to a single number
$success = $sdk->sendSMS('256712345678', 'Hello from PHP!');

// Send SMS to multiple numbers
$success = $sdk->sendSMS(['256712345678', '256787654321'], 'Hello to all!');

// Send SMS with custom sender ID and priority
$success = $sdk->sendSMS(
    ['256712345678'],
    'Hello!',
    'MyApp',
    MessagePriority::HIGH
);

// Get full API response
$response = $sdk->querySendSMS(
    ['256712345678'],
    'Hello!',
    'MyApp',
    MessagePriority::HIGHEST
);
```

### Checking Balance

```php
// Get balance as a float
$balance = $sdk->getBalance();
echo "Balance: $balance\n";

// Get full balance response
$response = $sdk->queryBalance();
echo "Status: {$response->status}\n";
echo "Balance: {$response->balance}\n";
echo "Currency: {$response->currency}\n";
```

### Configuration

```php
// Use sandbox environment
CommsSDK::useSandBox();

// Use live server (default)
CommsSDK::useLiveServer();

// Set custom sender ID
$sdk = $sdk->withSenderId('MyCustomSender');
```

---

## API Reference

### CommsSDK

#### Static Methods

- `CommsSDK::authenticate($userName, $apiKey): CommsSDK`
  - Authenticate and return SDK instance.
- `CommsSDK::useSandBox()`
  - Switch to sandbox environment.
- `CommsSDK::useLiveServer()`
  - Switch to live environment.

#### Instance Methods

- `withSenderId($senderId): CommsSDK`
  - Set sender ID, returns self for chaining.
- `sendSMS($numbers, $message, $senderId = null, $priority = MessagePriority::HIGHEST): bool`
  - Send SMS, returns boolean.
- `querySendSMS($numbers, $message, $senderId, $priority): ?ApiResponse`
  - Send SMS, returns full ApiResponse.
- `getBalance(): ?float`
  - Get account balance as float.
- `queryBalance(): ?ApiResponse`
  - Get full balance response as ApiResponse.
- `isAuthenticated(): bool`
  - Returns authentication status.
- `getApiKey()`
  - The API key used for authentication.
- `getUserName()`
  - The username used for authentication.
- `getSenderId()`
  - Current sender ID.

### Models

#### MessagePriority

- `MessagePriority::HIGHEST` - Priority "0"
- `MessagePriority::HIGH` - Priority "1"
- `MessagePriority::MEDIUM` - Priority "2"
- `MessagePriority::LOW` - Priority "3"
- `MessagePriority::LOWEST` - Priority "4"

#### ApiResponse

- `status` - Response status ("OK" or "Failed")
- `message` - Response message
- `cost` - Message cost
- `currency` - Currency code
- `msgFollowUpUniqueCode` - Unique tracking code
- `balance` - Account balance

---

## Error Handling

The SDK throws exceptions for authentication and validation errors:

```php
try {
    $sdk = CommsSDK::authenticate('', ''); // Empty credentials
} catch (InvalidArgumentException $e) {
    echo "Authentication error: " . $e->getMessage();
}

try {
    $sdk->sendSMS([], ''); // Empty numbers and message
} catch (InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

---

## Contributing

Bug reports and pull requests are welcome on GitHub.

---

## License

The SDK is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).

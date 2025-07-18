# Telegram API Forwarder in PHP

A lightweight PHP proxy/forwarder for the Telegram Bot API that supports **all HTTP methods**, unlimited upload and execution limits, and logs all requests and responses for easy debugging and monitoring.

---

## Features

- Forwards all HTTP methods (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD, etc.)
- Supports large uploads with unlimited PHP limits configured at runtime
- Logs every incoming request and Telegram response (headers, body, status) into a rotating log file
- Simple single-file implementation, easy to deploy on any PHP web server
- Transparent forwarding preserving request method, headers, and body

---

## Requirements

- PHP 7.4 or higher (recommended)
- cURL extension enabled
- Write permission for the directory to create the log file

---

## Installation

1. Clone this repository or copy the `telegram-api-proxy.php` file to your server.
2. Make sure the web server user has write access to the directory for logging.
3. Adjust PHP ini settings if necessary, although the script attempts to override limits.
4. Point your Telegram Bot API requests to the forwarder URL instead of directly to Telegram API.

---

## Usage

Send Telegram Bot API requests to your forwarder, for example:

```
https://yourdomain.com/telegram-api-proxy.php/bot<token>/sendMessage?chat_id=123&text=hello
```

The forwarder will proxy this request to:

```
https://api.telegram.org/bot)<token>/sendMessage?chat_id=123&text=hello
```

Preserving all headers and request body transparently.

---

## Logging

- All requests and responses are logged to `telegram_forwarder.log` in the script's directory.
- Log rotation occurs automatically when file size exceeds 10 MB.
- Logs include timestamps, request method, URI, headers, body, response status, and Telegram response.

---

## Security Note

This forwarder does **not** include authentication or rate limiting. It is recommended to protect access to the forwarder script via server config, IP whitelist, or other methods.

---

## License

MIT License

---

## Author

**Max Base**

- GitHub: [BaseMax](https://github.com/BaseMax)

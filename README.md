# NetCall

NetCall is a convenient and easy to use HTTP client. 
It is a wrapper around Guzzle, made for most common use cases.
And is designed to make development and testing easier and more pleasant.

### Author
Denis Mitrofanov

### Installation
```bash
composer require denismitr/net-call
```

### Usage
```php
$response = NetCall::new()->get('http://www.google.com?foo=bar');

// NetCallResponseInterface methods
$response->body(); // : string
$response->json(); // : array
$response->header('some-key);
$response->headers(); // : array
$response->status(); // : int
$response->isSuccess(); // : bool
$response->isOk(); // : bool
$response->isRedirect(); // : bool
$response->isServerError(); // : bool
```
```php
// request params will be json encoded by default
$response = NetCall::new()->post('http://test.com/post', [
    'foo' => 'bar',
    'baz' => 'qux',
]);

$response->json();
// array with json response data
```

From Params
```php
$response = NetCall::new()->asFormData()->post('http://myurl.com/post', [
    'foo' => 'bar',
    'baz' => 'qux',
]);
```

Multipart
```php
$response = NetCall::new()->asMultipart()->post('http://myurl.com/multi-part', [
    [
        'name' => 'foo',
        'contents' => 'bar'
    ],
    [
        'name' => 'baz',
        'contents' => 'qux',
    ],
    [
        'name' => 'test-file',
        'contents' => 'test contents',
        'filename' => 'test-file.txt',
    ],
]);
```

With Headers
```php
$response = NetCall::new()
    ->withHeaders(['Custom' => 'Header'])
    ->get('http://myurl.com/get');
```

Set Accept header
```php
$response = NetCall::new()
    ->accept('application/json')
    ->post('http://myurl.com/post');
```

Patch requests are supported
```php
$response = NetCall::new()->patch('http://myurl.com/patch', [
    'foo' => 'bar',
    'baz' => 'qux',
]);
```

### Exceptions
Exceptions are not thrown on 4xx and 5xx: use response status method instead.

### Redirects
Redirects are followed by default

To disable that:
```php
$response = NetCall::new()->noRedirects()->get('http://myurl.com/get');

$response->status(); // 302
$response->header('Location'); // http://myurl.com/redirected
```

### Auth

Basic auth
```php
$response = NetCall::new()
    ->withBasicAuth('username', 'password')
    ->get('http://myurl.com/basic-auth');
```

Digest auth
```php
$response = NetCall::new()
    ->withDigestAuth('username', 'password')
    ->get('http://myurl.com/digest-auth');
```

### Timeout

Set timeout
```php
NetCall::new()->timeout(1)->get('http://myurl.com/timeout');

// If more then a second passes
// \Denismitr\NetCall\Exceptions\NetCallException is thrown
```




<?php

use Denismitr\NetCall\NetCall;
use Denismitr\NetCall\NetCallResponse;

class NetCallTest extends \PHPUnit\Framework\TestCase
{
    public function url($url)
    {
        return vsprintf('%s/%s', [
            'http://localhost:9000',
            ltrim($url, '/'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET method
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function query_parameters_do_not_have_to_be_passed()
    {
        $response = NetCall::new()->get($this->url('/get'));

        $this->assertArraySubset([
            'query' => []
        ], $response->json());
    }
    
    /** @test */
    public function query_parameters_can_be_passed_as_an_array()
    {
        $response = NetCall::new()->get($this->url('/get'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function query_parameters_in_urls_are_respected()
    {
        $response = NetCall::new()->get($this->url('/get?foo=bar&baz=qux'));

        $this->assertArraySubset([
            'query' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function query_parameters_in_urls_can_be_combined_with_array_parameters()
    {
        $response = NetCall::new()->get($this->url('/get?foo=bar'), [
            'baz' => 'qux'
        ]);

        $this->assertArraySubset([
            'query' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /*
    |--------------------------------------------------------------------------
    | POST method
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function post_content_is_json_by_default()
    {
        $response = NetCall::new()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'content-type' => ['application/json'],
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function query_parameters_are_respected_in_post_requests()
    {
        $response = NetCall::new()->post($this->url('/post?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_form_params()
    {
        $response = NetCall::new()->asFormData()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'content-type' => ['application/x-www-form-urlencoded'],
            ],
            'form_params' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_json_explicitly()
    {
        $response = NetCall::new()->asJson()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'content-type' => ['application/json'],
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_multipart()
    {
        $response = NetCall::new()->asMultipart()->post($this->url('/multi-part'), [
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
        ])->json();

        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $response['body_content']);
        $this->assertTrue($response['has_file']);
        $this->assertEquals($response['file_content'], 'test contents');
        $this->assertStringStartsWith('multipart', $response['headers']['content-type'][0]);
    }

    /*
    |--------------------------------------------------------------------------
    | Additional headers
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function get_with_additional_headers()
    {
        $response = NetCall::new()->withHeaders(['Custom' => 'Header'])->get($this->url('/get'));

        $this->assertArraySubset([
            'headers' => [
                'custom' => ['Header'],
            ],
        ], $response->json());
    }

    /** @test */
    public function post_with_additional_headers()
    {
        $response = NetCall::new()->withHeaders(['Custom' => 'Header'])->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'custom' => ['Header'],
            ],
        ], $response->json());
    }

    /** @test */
    public function the_accept_header_can_be_set_via_shortcut()
    {
        $response = NetCall::new()->accept('banana/sandwich')->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'accept' => ['banana/sandwich'],
            ],
        ], $response->json());
    }

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function exceptions_are_not_thrown_for_40x_responses()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 418])->get($this->url('/get'));

        $this->assertEquals(418, $response->status());
    }

    /** @test */
    public function exceptions_are_not_thrown_for_50x_responses()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 508])->get($this->url('/get'));

        $this->assertEquals(508, $response->status());
    }

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function redirects_are_followed_by_default()
    {
        $response = NetCall::new()->get($this->url('/redirect'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Redirected!', $response->body());
    }

    /** @test */
    function redirects_can_be_disabled()
    {
        $response = NetCall::new()->noRedirects()->get($this->url('/redirect'));

        $this->assertEquals(302, $response->status());
        $this->assertEquals($this->url('/redirected'), $response->header('Location'));
    }

    /*
    |--------------------------------------------------------------------------
    | REST methods
    |--------------------------------------------------------------------------
    */

    function patch_requests_are_supported()
    {
        $response = NetCall::new()->patch($this->url('/patch'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    function put_requests_are_supported()
    {
        $response = NetCall::new()->put($this->url('/put'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    function delete_requests_are_supported()
    {
        $response = NetCall::new()->delete($this->url('/delete'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    function query_parameters_are_respected_in_put_requests()
    {
        $response = NetCall::new()->put($this->url('/put?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    function query_parameters_are_respected_in_patch_requests()
    {
        $response = NetCall::new()->patch($this->url('/patch?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /** @test */
    function query_parameters_are_respected_in_delete_requests()
    {
        $response = NetCall::new()->delete($this->url('/delete?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ]
        ], $response->json());
    }

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    /** @test */
    function can_retrieve_the_raw_response_body()
    {
        $response = NetCall::new()->get($this->url('/string-response'));

        $this->assertEquals("A simple string response", $response->body());
    }

    /** @test */
    function can_retrieve_response_header_values()
    {
        $response = NetCall::new()->get($this->url('/get'));

        $this->assertEquals('application/json', $response->header('Content-Type'));
        $this->assertEquals('application/json', $response->headers()['Content-Type']);
    }

    /** @test */
    function can_check_if_a_response_is_success()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 200])->get($this->url('/get'));

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    function can_check_if_a_response_is_redirect()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 302])->get($this->url('/get'));

        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    function can_check_if_a_response_is_client_error()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 404])->get($this->url('/get'));

        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    function can_check_if_a_response_is_server_error()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 508])->get($this->url('/get'));

        $this->assertTrue($response->isServerError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
    }

    /** @test */
    function is_ok_is_an_alias_for_is_success()
    {
        $response = NetCall::new()->withHeaders(['Z-Status' => 200])->get($this->url('/get'));

        $this->assertTrue($response->isOk());
        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /*
    |--------------------------------------------------------------------------
    | Callbacks
    |--------------------------------------------------------------------------
    */

    /** @test */
    function multiple_callbacks_can_be_run_before_sending_the_request()
    {
        $state = [];

        $response = NetCall::new()
            ->beforeSending(function ($request) use (&$state) {
                return tap($request, function ($request) use (&$state) {
                    $state['url'] = $request->url();
                    $state['method'] = $request->method();
                });
            })
            ->beforeSending(function ($request) use (&$state) {
                return tap($request, function ($request) use (&$state) {
                    $state['headers'] = $request->headers();
                    $state['body'] = $request->body();
                });
            })
            ->withHeaders(['Z-Status' => 200])
            ->post($this->url('/post'), ['foo' => 'bar']);

        $this->assertEquals($this->url('/post'), $state['url']);
        $this->assertEquals('POST', $state['method']);
        $this->assertArrayHasKey('User-Agent', $state['headers']);
        $this->assertEquals(200, $state['headers']['Z-Status']);
        $this->assertEquals(json_encode(['foo' => 'bar']), $state['body']);
    }

    /** @test */
    public function response_can_use_macros()
    {
        NetCallResponse::macro('testMacro', function () {
            return vsprintf('%s %s', [
                $this->json()['json']['foo'],
                $this->json()['json']['baz'],
            ]);
        });

        $response = NetCall::new()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertEquals('bar qux', $response->testMacro());
    }

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    /** @test */
    function can_use_basic_auth()
    {
        $response = NetCall::new()
            ->withBasicAuth('net-call', 'secret')
            ->get($this->url('/basic-auth'));

        $this->assertTrue($response->isOk());
    }

    /** @test */
    function can_use_digest_auth()
    {
        $response = NetCall::new()
            ->withDigestAuth('net-call', 'secret')
            ->get($this->url('/digest-auth'));

        $this->assertTrue($response->isOk());
    }

    /**
     * @test
     * @expectedException \Denismitr\NetCall\Exceptions\NetCallException
     */
    public function client_will_force_timeout()
    {
        NetCall::new()->timeout(1)->get($this->url('/timeout'));
    }
}
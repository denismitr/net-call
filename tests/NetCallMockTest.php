<?php

use Mockery as m;
use Denismitr\NetCall\NetCall;

class NetCallMockTest extends \PHPUnit\Framework\TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mock;

    public function setUp()
    {
        parent::setUp();

        $this->mock = m::mock(NetCall::class);
    }

    function tearDown()
    {
        m::close();

        NetCall::clearMock();
    }

    /** @test */
    public function it_can_mock_post_request()
    {
        NetCall::setTestMock($this->mock);

        $this->mock->shouldReceive('post')->once()->with('http://www.google.com', [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        NetCall::new()->post('http://www.google.com', [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
    }

    /** @test */
    public function it_can_mock_get_request()
    {
        NetCall::setTestMock($this->mock);

        $this->mock->shouldReceive('get')->once()->with('http://www.google.com?search=something');

        NetCall::new()->get('http://www.google.com?search=something');
    }
    
    /** @test */
    public function it_can_return_a_mocked_request()
    {
        NetCall::setTestMock($this->mock);

        $response = m::mock(\Denismitr\NetCall\Contracts\NetCallResponseInterface::class);

        $response->shouldReceive('json')->once();

        $this->mock->shouldReceive('post')->once()->with('http://www.google.com', [
            'foo' => 'bar',
            'baz' => 'qux',
        ])->andReturn($response);

        NetCall::new()->post('http://www.google.com', [
            'foo' => 'bar',
            'baz' => 'qux',
        ])->json();
    }
}
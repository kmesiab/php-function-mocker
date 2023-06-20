<?php declare(strict_types=1);

require_once __DIR__ . '/../src/MockGenerator.php';
require_once __DIR__ . '/../tests/Fixtures/MockableClass.php';

use Fixtures\MockableClass;
use Mockable\MockGenerator;
use PHPUnit\Framework\TestCase;

class CanAttachFunctionTest extends TestCase
{

    private MockGenerator $mocker;

    public function setUp(): void
    {
        $this->mocker = new MockGenerator(MockableClass::class);
    }

    public function tearDown(): void
    {
       $this->mocker->destroy();
    }

    public function testCanMockSimpleResponses(): void
    {
        $mockedResponse = 'Mocked Response';
        $mockedClass = $this->mocker->mockFunctionReturnValue('doStuff', $mockedResponse);
        $this->assertEquals($mockedResponse, $mockedClass->doStuff());
    }

    public function _testCanMutateFunctionBehavior(): void
    {
        $mockedClass = $this->mocker->mockFunction('doStuff', function(){
            return  1+1;
        });
        $this->assertSame(2, $mockedClass->doStuff());
    }
}

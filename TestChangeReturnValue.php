<?php

use MockableClass\Foo;

require_once 'MockGenerator.php';
require_once 'MockableClass/MockableClass.php';

$mocker = new FunctionMock\MockGenerator(Foo::class);

$mockedClass = $mocker->mockFunctionReturnValue('doStuff', 'Mocked Response');

echo $mockedClass->doStuff();

$mocker->destroy();

<?php

use MockableClass\Foo;

require_once 'MockGenerator.php';
require_once 'MockableClass/MockableClass.php';

$mocker = new FunctionMock\MockGenerator(Foo::class);
$mockedClass = $mocker->mockFunction('doStuff', function(){
    echo  1+1;
});

$mockedClass->doStuff();

$mocker->destroy();

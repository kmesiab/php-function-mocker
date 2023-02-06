# Mockable: Mocking Functions

***This little library is the start of a ridiculous notion, how can we mock a
function of a class under test (COT).***

Mockable is a library that lets you mutate the behavior of a dependent function 
within a "Class Under Test" enhancing coverage in your PHP Unit tests.


**What's a dependent function?**

Sometimes the class under test relies on inputs from an internal function.  Since we are
testing that class, we can't mock it, and therefore we cannot _change_ the input it receives
from the internal function it calls.  That internal function is the 'dependent function.'

Unlike Mockery's _[non deterministic built in PHP function mocking](https://github.com/php-mock/php-mock)_, Mockable lets you
**extend** the behavior of a dependent function and mutate its output.


### Usage

Assuming the following class under test:
```php
namespace MockableClass;

class Foo {

    public function doStuff()
    {
        return 'Bar';
    }

}
```


#### Mock a function's return value with a string
```php
use MockableClass\Foo;

require_once 'MockGenerator.php';
require_once 'MockableClass/MockableClass.php';

$mocker = new FunctionMock\MockGenerator(Foo::class);

$mockedClass = $mocker->mockFunctionReturnValue('doStuff', 'Mocked Response');

$this->assertSame($mockedClass->doStuff(), 'Mocked Response');

$mocker->destroy();

```

#### Mock a class function with a callable or closure
```php

use MockableClass\Foo;

require_once 'MockGenerator.php';
require_once 'MockableClass/MockableClass.php';

$mocker = new FunctionMock\MockGenerator(Foo::class);
$mockedClass = $mocker->mockFunction('doStuff', function(){
    return  1 + 1;
});

$this->assertSame($mockedClass->doStuff(), 2);

$mocker->destroy();

```
---

### Philosophy

PHPUnit, or most testing frameworks for that matter don't have a way
to mock the response value of a function in the class being tested.

There are many reasons why some feel this is bad.  Those people aren't me.
There are instances when it is not practical to inject a dependency
that modifys the runtime behavior of a class being tested.

(insert other obvious use cases here when you have time)

### Methodology

Given a class you wish to test, and a function you wish to modify, this 
library will rewrite the source code of that class, injecting new behavior
for the mocked function, then instantiate an instance of this new mocked
class, returning it for you to test as your heart desires.

### Complaints

- Please email complaints to [Tim Bond](mailto:tim.bond@mheducation.com)
- Please email accolades to[ Kevin Mesiab](mailto:kevin.mesiab@mheducation.com)
- If you find a bug, don't complain, submit a PR. 

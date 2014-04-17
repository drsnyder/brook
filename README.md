# Brook

A lightweight concurrency framework for PHP based on zeromq.

## Summary

Brook was created to make concurrent stream processing more accessible in PHP. It's built on top of
[zeromq](http://zeromq.org/) and uses the fan-out pattern to distribute work.


## Usage

Here is a simple examlpe where 20 messages are fanned out and multiplied then collected and confirmed back
in the main thread of execution.

     class MultiplicationTask implements \Brook\TaskInterface {
       const MULTIPLIER = 2;

       public function setup() { }
       public function tearDown() { }

       public function work($value) {
         return sprintf("%d %d", $value, $value * self::MULTIPLIER);
       }
     }


     $messageCount = 20;

     $fanOut = new Brook\FanOut();
     $fanOut->distributeWork(2, new MultiplicationTask());

     for ($i=1; $i<=$messageCount; $i++) {
       $fanOut->enqueue($i);
       echo "sent $i", PHP_EOL;
     }

     for ($i=1; $i<=$messageCount; $i++) {
       $result = $fanOut->readFromSink();
       list($value, $multiplied) = explode(' ', $result);

       echo "got $value, $multiplied", PHP_EOL;
       assert($multiplied == (MultiplicationTask::MULTIPLIER * $value));
     }

     $fanOut->shutdown();


## LICENSE (MIT)

Copyright (c) 2014 Damon Snyder

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

<?php

namespace DiabloMedia\PHPUnit\Printer;

class PrettyPrinter extends \PHPUnit_TextUI_ResultPrinter implements \PHPUnit_Framework_TestListener
{
    protected $className;
    protected $testName;
    protected $previousClassName;
    protected $previousTestName;
    protected $timeColors;

    protected $defaultTimeColors = [
            '1'    => 'fg-red',
            '.400' => 'fg-yellow',
            '0'    => 'fg-green',
    ];

    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->debug && is_null($this->timeColors)) {
            if (defined('DIABLO_PRINTER_TIME_COLORS') && is_array(DIABLO_PRINTER_TIME_COLORS)) {
                $this->timeColors = DIABLO_PRINTER_TIME_COLORS;
                krsort($this->timeColors, SORT_NUMERIC);
            } else {
                $this->timeColors = $this->defaultTimeColors;
            }
        }

        parent::startTestSuite($suite);
    }

    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $this->className = get_class($test);
        $this->testName = $test->getName(false);

        if (!$this->debug) {
            parent::startTest($test);
        }
    }

    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        parent::endTest($test, $time);

        if ($this->debug) {
            foreach ($this->timeColors as $threshold => $color) {
                if ($time >= $threshold) {
                    $timeColor = $color;
                    break;
                }
            }

            $this->write(' ');
            $this->writeWithColor($timeColor, '['.number_format($time, 3).'s]', false);
            $this->write(' ');
            $this->writeWithColor('fg-cyan', \PHPUnit_Util_Test::describe($test), true);
        }
    }

    protected function writeProgress($progress)
    {
        if ($this->debug) {
            $this->write($progress);
            ++$this->numTestsRun;
        } else {
            if ($this->previousClassName !== $this->className) {
                if ($this->previousTestName != null) {
                    $this->write("\n\n");
                }
                
                $this->writeWithColor('fg-blue', $this->className);

                $this->previousTestName = null;
            }

           if ($this->previousTestName !== $this->testName) {
                $this->write("\n");
                $this->writeWithColor('fg-magenta', str_pad($this->testName, 50, ' ', STR_PAD_LEFT).' ', false);
            }

            $this->previousClassName = $this->className;
            $this->previousTestName = $this->testName;

            if ($progress == '.') {
                $this->writeWithColor('fg-green', $progress, false);
            } else {
                $this->write($progress);
            }
        }
    }

    protected function printDefectTrace(\PHPUnit_Framework_TestFailure $defect)
    {
        $this->write($this->formatExceptionMsg($defect->getExceptionAsString()));
        $trace = \PHPUnit_Util_Filter::getFilteredStacktrace(
            $defect->thrownException()
        );
        if (!empty($trace)) {
            $this->write("\n".$trace);
        }
        $exception = $defect->thrownException()->getPrevious();
        while ($exception) {
            $this->write(
            "\nCaused by\n".
            \PHPUnit_Framework_TestFailure::exceptionToString($e)."\n".
            \PHPUnit_Util_Filter::getFilteredStacktrace($e)
          );
            $exception = $exception->getPrevious();
        }
    }

    protected function formatExceptionMsg($exceptionMessage)
    {
        $exceptionMessage = str_replace("+++ Actual\n", '', $exceptionMessage);
        $exceptionMessage = str_replace("--- Expected\n", '', $exceptionMessage);
        $exceptionMessage = str_replace('@@ @@', '', $exceptionMessage);

        if ($this->colors) {
            $exceptionMessage = preg_replace('/^(Exception.*)$/m', "\033[01;31m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace('/(Failed.*)$/m', "\033[01;31m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace("/(\-+.*)$/m", "\033[01;32m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace("/(\++.*)$/m", "\033[01;31m$1\033[0m", $exceptionMessage);
        }

        return $exceptionMessage;
    }
}

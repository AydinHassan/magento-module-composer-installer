<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy\Exception;

/**
 * Class SourceNotExistsExceptionTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class SourceNotExistsExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSourceNotExistsException()
    {
        $e = new SourceNotExistsException('/some/file.txt');
        $this->assertSame('Source "/some/file.txt" does not exist', $e->getMessage());
        $this->assertSame('/some/file.txt', $e->getSourceFilePath());
    }
}

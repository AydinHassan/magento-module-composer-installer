<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy\Exception;

/**
 * Class TargetExistsExceptionTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class TargetExistsExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testTargetExistsException()
    {
        $e = new TargetExistsException('/some/file.txt');
        $this->assertSame(
            'Target "/some/file.txt" already exists (set extra.magento-force to override)',
            $e->getMessage()
        );
        $this->assertSame('/some/file.txt', $e->getTargetFilePath());
    }
}

<?php

namespace MagentoHackathon\Composer\Magento\Listener;

use MagentoHackathon\Composer\Magento\Event\InstallEvent;
use PHPUnit_Framework_TestCase;

/**
 * Class CheckAndCreateMagentoRootDirTest
 * @package MagentoHackathon\Composer\Magento\Listener
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CheckAndCreateMagentoRootDirTest extends PHPUnit_Framework_TestCase
{
    public function testMagentoRootDirIsCreatedIfItDoesNotExist()
    {
        $projectLocation = sprintf('%s/%s/root', sys_get_temp_dir(), $this->getName(false));
        mkdir($projectLocation, 0777, true);
        $oldWd = getcwd();
        chdir($projectLocation);
        $expectedDirectory = sprintf('%s/htdocs', $projectLocation);

        $this->assertFalse(is_dir($expectedDirectory));

        $listener = new CheckAndCreateMagentoRootDirListener('htdocs');
        $listener->__invoke(new InstallEvent('pre-install', array()));

        $this->assertTrue(is_dir($expectedDirectory));
        chdir($oldWd);
        rmdir($expectedDirectory);
        rmdir($projectLocation);
    }
}

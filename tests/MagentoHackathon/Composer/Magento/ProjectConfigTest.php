<?php

namespace MagentoHackathon\Composer\Magento;

use PHPUnit_Framework_TestCase;

/**
 * Class ProjectConfigTest
 * @package MagentoHackathon\Composer\Magento
 */
class ProjectConfigTest extends PHPUnit_Framework_TestCase
{
    public function testTransformArrayKeysToLowerCase()
    {
        $config = new ProjectConfig(array(), array());

        $array = array('ONE' => 1, 'TWO' => 2, 'THREE' => 3);
        $expected = array('one' => 1, 'two' => 2, 'three' => 3);
        $this->assertSame($expected, $config->transformArrayKeysToLowerCase($array));
    }

    public function testHasDeployStrategy()
    {
        $config = new ProjectConfig(array('magento-deploystrategy' => 1), array());
        $this->assertTrue($config->hasDeployStrategy());
        $config = new ProjectConfig(array(), array());
        $this->assertFalse($config->hasDeployStrategy());
    }

    public function testHasDeployStrategyOverwrite()
    {
        $config = new ProjectConfig(array('magento-deploystrategy-overwrite' => 1), array());
        $this->assertTrue($config->hasDeployStrategyOverwrite());
        $config = new ProjectConfig(array(), array());
        $this->assertFalse($config->hasDeployStrategyOverwrite());
    }

    public function testHasMagentoDeployIgnore()
    {
        $config = new ProjectConfig(array('magento-deploy-ignore' => 1), array());
        $this->assertTrue($config->hasMagentoDeployIgnore());
        $config = new ProjectConfig(array(), array());
        $this->assertFalse($config->hasMagentoDeployIgnore());
    }

    public function testHasAutoAppendGitignore()
    {
        $config = new ProjectConfig(array('auto-append-gitignore' => 1), array());
        $this->assertTrue($config->hasAutoAppendGitignore());
        $config = new ProjectConfig(array(), array());
        $this->assertFalse($config->hasAutoAppendGitignore());
    }

    public function testHasPathMappingTranslations()
    {
        $config = new ProjectConfig(array('path-mapping-translations' => 1), array());
        $this->assertTrue($config->hasPathMappingTranslations());
        $config = new ProjectConfig(array(), array());
        $this->assertFalse($config->hasPathMappingTranslations());
    }

    public function testGetSortPriorities()
    {
        $config = new ProjectConfig(array('magento-deploy-sort-priority' => array(1)), array());
        $this->assertSame(array(1), $config->getSortPriorities());
        $config = new ProjectConfig(array(), array());
        $this->assertSame(array(), $config->getSortPriorities());
    }

    public function testGetVendorDir()
    {
        $config = new ProjectConfig(array(), array('vendor-dir' => 'vendor'));
        $this->assertSame('vendor', $config->getVendorDir());
        $config = new ProjectConfig(array(), array());
        $this->assertNull($config->getVendorDir());
    }

    public function testGetMagentoMapOverwrite()
    {
        $config = new ProjectConfig(array('magento-map-overwrite' => array(1)), array());
        $this->assertSame(array(1), $config->getMagentoMapOverwrite());
        $config = new ProjectConfig(array(), array());
        $this->assertSame(array(), $config->getMagentoMapOverwrite());
    }

    public function testGetDeployStrategyOverwrite()
    {
        $config = new ProjectConfig(array('magento-deploystrategy-overwrite' => array(1)), array());
        $this->assertSame(array(1), $config->getDeployStrategyOverwrite());
        $config = new ProjectConfig(array(), array());
        $this->assertSame(array(), $config->getDeployStrategyOverwrite());
    }

    public function testGetPathMappingTranslations()
    {
        $config = new ProjectConfig(array('path-mapping-translations' => array(1)), array());
        $this->assertSame(array(1), $config->getPathMappingTranslations());
        $config = new ProjectConfig(array(), array());
        $this->assertSame(array(), $config->getPathMappingTranslations());
    }

    public function testGetForce()
    {
        $config = new ProjectConfig(array('magento-force' => true), array());
        $this->assertTrue($config->getMagentoForce());
        $this->assertTrue($config->getMagentoForceByPackageName('some/package'));

        $config = new ProjectConfig(array('magento-force' => false), array());
        $this->assertFalse($config->getMagentoForce());
        $this->assertFalse($config->getMagentoForceByPackageName('some/package'));

        $config = new ProjectConfig(array('magento-force' => array('lol')), array());
        $this->assertTrue($config->getMagentoForce());
        $this->assertTrue($config->getMagentoForceByPackageName('some/package'));
    }

    public function testGetMagentoDeployIgnore()
    {
        $config = new ProjectConfig(array('magento-deploy-ignore' => array(1)), array());
        $this->assertSame(array(1), $config->getMagentoDeployIgnore());
        $config = new ProjectConfig(array(), array());
        $this->assertSame(array(), $config->getMagentoDeployIgnore());
    }

    public function testGetDeployStrategy()
    {
        $config = new ProjectConfig(array('magento-deploystrategy' => 'symlink'), array());
        $this->assertSame('symlink', $config->getDeployStrategy());

        $config = new ProjectConfig(array('magento-deploystrategy' => ' symlink   '), array());
        $this->assertSame('symlink', $config->getDeployStrategy());
    }

    public function testGetMagentoRootDir()
    {
        $config = new ProjectConfig(array('magento-root-dir' => '/htdocs/'), array());
        $this->assertSame('/htdocs', $config->getMagentoRootDir());

        $config = new ProjectConfig(array('magento-root-dir' => 'htdocs/'), array());
        $this->assertSame('htdocs', $config->getMagentoRootDir());

        $config = new ProjectConfig(array(), array());
        $this->assertSame('root', $config->getMagentoRootDir());
    }

    public function testGetInstalledModuleRepositoryFile()
    {
        $config = new ProjectConfig(array('magento-root-dir' => '/htdocs/'), array('vendor-dir' => 'vendor'));
        $this->assertSame('vendor/magento-installed.json', $config->getModuleRepositoryLocation());

        $config = new ProjectConfig(array('module-repository-location' => 'htdocs'), array('vendor-dir'));
        $this->assertSame('htdocs/magento-installed.json', $config->getModuleRepositoryLocation());
    }
}

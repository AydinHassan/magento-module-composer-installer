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
        $config = new ProjectConfig([], []);

        $array = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3];
        $expected = ['one' => 1, 'two' => 2, 'three' => 3];
        $this->assertSame($expected, $config->transformArrayKeysToLowerCase($array));
    }

    public function testHasDeployStrategy()
    {
        $config = new ProjectConfig(['magento-deploystrategy' => 1], []);
        $this->assertTrue($config->hasDeployStrategy());
        $config = new ProjectConfig([], []);
        $this->assertFalse($config->hasDeployStrategy());
    }

    public function testHasDeployStrategyOverwrite()
    {
        $config = new ProjectConfig(['magento-deploystrategy-overwrite' => 1], []);
        $this->assertTrue($config->hasDeployStrategyOverwrite());
        $config = new ProjectConfig([], []);
        $this->assertFalse($config->hasDeployStrategyOverwrite());
    }

    public function testHasMagentoDeployIgnore()
    {
        $config = new ProjectConfig(['magento-deploy-ignore' => 1], []);
        $this->assertTrue($config->hasMagentoDeployIgnore());
        $config = new ProjectConfig([], []);
        $this->assertFalse($config->hasMagentoDeployIgnore());
    }

    public function testHasAutoAppendGitignore()
    {
        $config = new ProjectConfig(['auto-append-gitignore' => 1], []);
        $this->assertTrue($config->hasAutoAppendGitignore());
        $config = new ProjectConfig([], []);
        $this->assertFalse($config->hasAutoAppendGitignore());
    }

    public function testHasPathMappingTranslations()
    {
        $config = new ProjectConfig(['path-mapping-translations' => 1], []);
        $this->assertTrue($config->hasPathMappingTranslations());
        $config = new ProjectConfig([], []);
        $this->assertFalse($config->hasPathMappingTranslations());
    }

    public function testGetSortPriorities()
    {
        $config = new ProjectConfig(['magento-deploy-sort-priority' => [1]], []);
        $this->assertSame([1], $config->getSortPriorities());
        $config = new ProjectConfig([], []);
        $this->assertSame([], $config->getSortPriorities());
    }

    public function testGetVendorDir()
    {
        $config = new ProjectConfig([], ['vendor-dir' => 'vendor']);
        $this->assertSame('vendor', $config->getVendorDir());
        $config = new ProjectConfig([], []);
        $this->assertNull($config->getVendorDir());
    }

    public function testGetMagentoMapOverwrite()
    {
        $config = new ProjectConfig(['magento-map-overwrite' => [1]], []);
        $this->assertSame([1], $config->getMagentoMapOverwrite());
        $config = new ProjectConfig([], []);
        $this->assertSame([], $config->getMagentoMapOverwrite());
    }

    public function testGetDeployStrategyOverwrite()
    {
        $config = new ProjectConfig(['magento-deploystrategy-overwrite' => [1]], []);
        $this->assertSame([1], $config->getDeployStrategyOverwrite());
        $config = new ProjectConfig([], []);
        $this->assertSame([], $config->getDeployStrategyOverwrite());
    }

    public function testGetPathMappingTranslations()
    {
        $config = new ProjectConfig(['path-mapping-translations' => [1]], []);
        $this->assertSame([1], $config->getPathMappingTranslations());
        $config = new ProjectConfig([], []);
        $this->assertSame([], $config->getPathMappingTranslations());
    }

    public function testGetForce()
    {
        $config = new ProjectConfig(['magento-force' => true], []);
        $this->assertTrue($config->getMagentoForce());
        $this->assertTrue($config->getMagentoForceByPackageName('some/package'));

        $config = new ProjectConfig(['magento-force' => false], []);
        $this->assertFalse($config->getMagentoForce());
        $this->assertFalse($config->getMagentoForceByPackageName('some/package'));

        $config = new ProjectConfig(['magento-force' => ['lol']], []);
        $this->assertTrue($config->getMagentoForce());
        $this->assertTrue($config->getMagentoForceByPackageName('some/package'));
    }

    public function testGetMagentoDeployIgnore()
    {
        $config = new ProjectConfig(['magento-deploy-ignore' => [1]], []);
        $this->assertSame([1], $config->getMagentoDeployIgnore());
        $config = new ProjectConfig([], []);
        $this->assertSame([], $config->getMagentoDeployIgnore());
    }

    public function testGetDeployStrategy()
    {
        $config = new ProjectConfig(['magento-deploystrategy' => 'symlink'], []);
        $this->assertSame('symlink', $config->getDeployStrategy());

        $config = new ProjectConfig(['magento-deploystrategy' => ' symlink   '], []);
        $this->assertSame('symlink', $config->getDeployStrategy());
    }

    public function testGetMagentoRootDir()
    {
        $config = new ProjectConfig(['magento-root-dir' => '/htdocs/'], []);
        $this->assertSame('/htdocs', $config->getMagentoRootDir(false));

        $config = new ProjectConfig(['magento-root-dir' => 'htdocs/'], []);
        $this->assertSame('htdocs', $config->getMagentoRootDir(false));

        $config = new ProjectConfig([], []);
        $this->assertSame('root', $config->getMagentoRootDir(false));
    }

    public function testGetInstalledModuleRepositoryFile()
    {
        $config = new ProjectConfig(['magento-root-dir' => '/htdocs/'], ['vendor-dir' => 'vendor']);
        $this->assertSame('vendor/magento-installed.json', $config->getModuleRepositoryLocation());

        $config = new ProjectConfig(['module-repository-location' => 'htdocs'], ['vendor-dir']);
        $this->assertSame('htdocs/magento-installed.json', $config->getModuleRepositoryLocation());
    }
}

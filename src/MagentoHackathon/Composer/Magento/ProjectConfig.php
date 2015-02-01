<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use SebastianBergmann\Exporter\Exception;

/**
 * Class ProjectConfig
 * @package MagentoHackathon\Composer\Magento
 */
class ProjectConfig
{
    // Config Keys
    const EXTRA_KEY                                 = 'extra';
    const SORT_PRIORITY_KEY                         = 'magento-deploy-sort-priority';
    const MAGENTO_ROOT_DIR_KEY                      = 'magento-root-dir';
    const MAGENTO_DEPLOY_STRATEGY_KEY               = 'magento-deploystrategy';
    const MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY     = 'magento-deploystrategy-overwrite';
    const MAGENTO_MAP_OVERWRITE_KEY                 = 'magento-map-overwrite';
    const MAGENTO_DEPLOY_IGNORE_KEY                 = 'magento-deploy-ignore';
    const MAGENTO_FORCE_KEY                         = 'magento-force';
    const AUTO_APPEND_GITIGNORE_KEY                 = 'auto-append-gitignore';
    const PATH_MAPPINGS_TRANSLATIONS_KEY            = 'path-mapping-translations';
    const MODULE_REPOSITORY_LOCATION_KEY            = 'module-repository-location';

    // Default Values
    const DEFAULT_MAGENTO_ROOT_DIR = 'root';

    /**
     * @var array
     */
    protected $extra;

    /**
     * @var array
     */
    protected $composerConfig;

    /**
     * @param array $extra
     * @param array $composerConfig
     */
    public function __construct(array $extra, array $composerConfig)
    {
        $this->extra            = $extra;
        $this->composerConfig   = $composerConfig;
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function fetchVarFromConfigArray(array $array, $key, $default = null)
    {
        $result = $default;
        if (isset($array[$key])) {
            $result = $array[$key];
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function fetchVarFromExtraConfig($key, $default = null)
    {
        return $this->fetchVarFromConfigArray($this->extra, $key, $default);
    }

    /**
     * @return string
     */
    public function getMagentoRootDir()
    {
        return rtrim(
            trim(
                $this->fetchVarFromExtraConfig(
                    self::MAGENTO_ROOT_DIR_KEY,
                    self::DEFAULT_MAGENTO_ROOT_DIR
                )
            ),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return string
     */
    public function getDeployStrategy()
    {
        return trim((string) $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_KEY));
    }

    /**
     * @return bool
     */
    public function hasDeployStrategy()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_STRATEGY_KEY);
    }

    /**
     * @return array
     */
    public function getDeployStrategyOverwrite()
    {
        return $this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY, array())
        );
    }

    /**
     * @return bool
     */
    public function hasDeployStrategyOverwrite()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY);
    }

    /**
     * @return array
     */
    public function getMagentoDeployIgnore()
    {
        return $this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_IGNORE_KEY, array())
        );
    }

    /**
     * @return bool
     */
    public function hasMagentoDeployIgnore()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_IGNORE_KEY);
    }

    /**
     * @return bool
     */
    public function getMagentoForce()
    {
        return (bool) $this->fetchVarFromExtraConfig(self::MAGENTO_FORCE_KEY);
    }

    /**
     * @param string $packagename
     * @return string
     */
    public function getMagentoForceByPackageName($packagename)
    {
        return $this->getMagentoForce();
    }

    /**
     * @return bool
     */
    public function hasAutoAppendGitignore()
    {
        return $this->hasExtraField(self::AUTO_APPEND_GITIGNORE_KEY);
    }

    /**
     * @return array
     */
    public function getPathMappingTranslations()
    {
        return $this->fetchVarFromExtraConfig(self::PATH_MAPPINGS_TRANSLATIONS_KEY, array());
    }

    /**
     * @return bool
     */
    public function hasPathMappingTranslations()
    {
        return $this->hasExtraField(self::PATH_MAPPINGS_TRANSLATIONS_KEY);
    }

    /**
     * @return array
     */
    public function getMagentoMapOverwrite()
    {
        return $this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_MAP_OVERWRITE_KEY, array())
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function hasExtraField($key)
    {
        return !is_null($this->fetchVarFromExtraConfig($key));
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function transformArrayKeysToLowerCase(array $array)
    {
        return array_change_key_case($array, CASE_LOWER);
    }

    /**
     * Get Composer vendor directory
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->fetchVarFromConfigArray($this->composerConfig, 'vendor-dir');
    }

    /**
     * Get Package Sort Order
     *
     * @return array
     */
    public function getSortPriorities()
    {
        return $this->fetchVarFromConfigArray($this->extra, self::SORT_PRIORITY_KEY, array());
    }

    /**
     * @return string
     */
    public function getModuleRepositoryLocation()
    {
        $moduleRepoDir = $this->fetchVarFromExtraConfig(
            self::MODULE_REPOSITORY_LOCATION_KEY,
            $this->fetchVarFromConfigArray(
                $this->composerConfig,
                'vendor-dir'
            )
        );

        return sprintf('%s/magento-installed.json', $moduleRepoDir);
    }
}

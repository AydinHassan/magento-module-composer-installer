<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Parsers modman files
 */
class ModmanParser implements ParserInterface
{

    /**
     * @var \SplFileObject The modman file
     */
    protected $file;

    /**
     *
     * @param string $modManFile
     */
    public function __construct($modManFile)
    {
        $this->file = new \SplFileObject($modManFile);
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function getMappings()
    {
        if (!$this->file->isReadable()) {
            throw new \ErrorException(sprintf('modman file "%s" not readable', $this->file->getPathname()));
        }

        $map = $this->parseMappings();
        return $map;
    }

    /**
     * @throws \ErrorException
     * @return array
     */
    protected function parseMappings()
    {
        $map = [];
        foreach ($this->file as $line => $row) {
            $row = trim($row);
            if ('' === $row || in_array($row[0], ['#', '@'])) {
                continue;
            }
            $parts = preg_split('/\s+/', $row, 2, PREG_SPLIT_NO_EMPTY);
            if (count($parts) != 2) {
                throw new \ErrorException(
                    sprintf('Invalid row on line %d has %d parts, expected 2', $line, count($row))
                );
            }
            $map[] = $parts;
        }
        return $map;
    }
}

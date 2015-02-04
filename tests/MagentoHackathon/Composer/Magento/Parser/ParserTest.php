<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Factory\ParserFactory;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use PHPUnit_Framework_TestCase;

/**
 * Class ParserTest
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testParserReturnsMapCollection()
    {
        $extra = ['map' => [
            ['file1', 'file1'],
            ['file2', 'file2'],
            ['file3', 'file3'],
        ]];

        $package    = new Package('some/package', '1.0.0', 'some/package');
        $package->setExtra($extra);
        $parser     = new Parser(new ParserFactory(new ProjectConfig([], [])));
        $result     = $parser->getMappings($package, '/package/source', '/install/directory');

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Map\MapCollection', $result);

        foreach ($result as $key => $item) {
            /** @var Map $item */

            $map = $extra['map'][$key];

            $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Map\Map', $item);
            $this->assertSame($map[0], $item->getRawSource());
            $this->assertSame($map[1], $item->getRawDestination());

            $absoluteSource = '/package/source/' . $map[0];
            $absoluteDestination = '/install/directory/' . $map[1];

            $this->assertSame($absoluteSource, $item->getAbsoluteSource());
            $this->assertSame($absoluteDestination, $item->getAbsoluteDestination());
        }
    }
}

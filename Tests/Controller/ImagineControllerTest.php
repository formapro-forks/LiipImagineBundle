<?php

namespace Liip\ImagineBundle\Tests\Controller;

use Liip\ImagineBundle\Controller\ImagineController;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * @covers Liip\ImagineBundle\Controller\ImagineController
 */
class ImagineControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithExpectedServices()
    {
        new ImagineController(
            $this->createDataManagerMock(),
            $this->createFilterManagerMock(),
            $this->createCacheManagerMock(),
            $this->createUriSignerMock()
        );
    }

    public function testShouldResolveIfCacheStored()
    {
        $expectedPath = 'theImage';
        $expectedFilter = 'theFilter';
        $expectedUrl = 'http://example.com/media/cache/theFilter/theImage';

        $dataManagerMock = $this->createDataManagerMock();
        $dataManagerMock
            ->expects($this->never())
            ->method('find')
        ;

        $filterManagerMock = $this->createFilterManagerMock();
        $filterManagerMock
            ->expects($this->never())
            ->method('applyFilter')
        ;

        $cacheManagerMock = $this->createCacheManagerMock();
        $cacheManagerMock
            ->expects($this->once())
            ->method('isStored')
            ->with($expectedPath, $expectedFilter)
            ->will($this->returnValue(true))
        ;
        $cacheManagerMock
            ->expects($this->once())
            ->method('resolve')
            ->with($expectedPath, $expectedFilter)
            ->will($this->returnValue($expectedUrl))
        ;

        $controller = new ImagineController(
            $dataManagerMock,
            $filterManagerMock,
            $cacheManagerMock,
            $this->createUriSignerMock()
        );

        $response = $controller->filterAction(new Request(), $expectedPath, $expectedFilter);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    public function testShouldApplyFiltersBeforeResolveIfCacheNotStored()
    {
        $expectedPath = 'theImage';
        $expectedFilter = 'theFilter';
        $expectedBinary = new Binary('aContent', 'aMimeType', 'aFormat');
        $expectedFilteredBinary = new Binary('aContent', 'aMimeType', 'aFormat');
        $expectedUrl = 'http://example.com/media/cache/theFilter/theImage';

        $dataManagerMock = $this->createDataManagerMock();
        $dataManagerMock
            ->expects($this->once())
            ->method('find')
            ->with($expectedFilter, $expectedPath)
            ->will($this->returnValue($expectedBinary))
        ;

        $filterManagerMock = $this->createFilterManagerMock();
        $filterManagerMock
            ->expects($this->once())
            ->method('applyFilter')
            ->with(
                $this->identicalTo($expectedBinary),
                $expectedFilter,
                $runtimeConfig = array()
            )
            ->will($this->returnValue($expectedFilteredBinary))
        ;

        $cacheManagerMock = $this->createCacheManagerMock();
        $cacheManagerMock
            ->expects($this->once())
            ->method('isStored')
            ->with(
                $expectedPath,
                $expectedFilter
            )
            ->will($this->returnValue(false))
        ;
        $cacheManagerMock
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->identicalTo($expectedFilteredBinary),
                $expectedPath,
                $expectedFilter
            )
            ->will($this->returnValue(false))
        ;
        $cacheManagerMock
            ->expects($this->once())
            ->method('resolve')
            ->with($expectedPath, $expectedFilter)
            ->will($this->returnValue($expectedUrl))
        ;

        $controller = new ImagineController(
            $dataManagerMock,
            $filterManagerMock,
            $cacheManagerMock,
            $this->createUriSignerMock()
        );

        $response = $controller->filterAction(new Request(), $expectedPath, $expectedFilter);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DataManager
     */
    protected function createDataManagerMock()
    {
        return $this->getMock('Liip\ImagineBundle\Imagine\Data\DataManager', array(), array(), '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterManager
     */
    protected function createFilterManagerMock()
    {
        return $this->getMock('Liip\ImagineBundle\Imagine\Filter\FilterManager', array(), array(), '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheManager
     */
    protected function createCacheManagerMock()
    {
        return $this->getMock('Liip\ImagineBundle\Imagine\Cache\CacheManager', array(), array(), '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UriSigner
     */
    protected function createUriSignerMock()
    {
        return $this->getMock('Symfony\Component\HttpKernel\UriSigner', array(), array(), '', false);
    }
}

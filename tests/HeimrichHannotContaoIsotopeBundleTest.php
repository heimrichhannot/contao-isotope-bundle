<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Tests;

use HeimrichHannot\IsotopeBundle\DependencyInjection\IsotopeExtension;
use HeimrichHannot\IsotopeBundle\HeimrichHannotContaoIsotopeBundle;
use PHPUnit\Framework\TestCase;

class HeimrichHannotContaoIsotopeBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $bundle = new HeimrichHannotContaoIsotopeBundle();
        $this->assertInstanceOf(HeimrichHannotContaoIsotopeBundle::class, $bundle);
    }

    /**
     * Tests the getContainerExtension() method.
     */
    public function testReturnsTheContainerExtension()
    {
        $bundle = new HeimrichHannotContaoIsotopeBundle();
        $this->assertInstanceOf(IsotopeExtension::class, $bundle->getContainerExtension());
    }
}

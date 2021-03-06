<?php

namespace ApplicationTest\Entity\Base;

use PHPUnit_Framework_TestCase;

class TrackedTest extends PHPUnit_Framework_TestCase
{

	/**
	 *
	 * @var \Application\Entity\Base\Tracked
	 */
	private $_sut;

	protected function setUp()
	{
		$this->_sut = $this->getMockForAbstractClass('Application\Entity\Base\Tracked');
	}

	/**
	 * @test
	 * @group entity
	 * @group jfoushee
	 */
	public function createdTsShouldReturnDateTime()
	{
		$this->assertInstanceOf('\DateTime', $this->_sut->getCreatedTs());
	}

	/**
	 * @test
	 * @group entity
	 * @group jfoushee
	 */
	public function lastModifiedTsShouldReturnDateTime()
	{
		$this->assertInstanceOf('\DateTime', $this->_sut->getLastModifiedTs());
	}

}

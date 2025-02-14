<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use ReflectionClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\DataModel\Entity\EntityId
 * @uses \Wikibase\DataModel\Entity\ItemId
 * @uses \Wikibase\DataModel\Entity\NumericPropertyId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityIdTest extends \PHPUnit\Framework\TestCase {

	public function instanceProvider() {
		$ids = [];

		$ids[] = [ new ItemId( 'Q1' ), '' ];
		$ids[] = [ new ItemId( 'Q42' ), '' ];
		$ids[] = [ new ItemId( 'Q31337' ), '' ];
		$ids[] = [ new ItemId( 'Q2147483647' ), '' ];
		$ids[] = [ new ItemId( ':Q2147483647' ), '' ];
		$ids[] = [ new ItemId( 'foo:Q2147483647' ), 'foo' ];
		$ids[] = [ new NumericPropertyId( 'P101010' ), '' ];
		$ids[] = [ new NumericPropertyId( 'foo:bar:P101010' ), 'foo' ];

		return $ids;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testEqualsSimple( EntityId $id ) {
		$this->assertTrue( $id->equals( $id ) );
		$this->assertTrue( $id->equals( unserialize( serialize( $id ) ) ) );
		$this->assertFalse( $id->equals( $id->getSerialization() ) );
		$this->assertFalse( $id->equals( $id->getEntityType() ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerializationRoundtrip( EntityId $id ) {
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	public function testDeserializationCompatibility() {
		$v05serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":15:{["item","Q123"]}';

		$this->assertEquals(
			new ItemId( 'q123' ),
			unserialize( $v05serialization )
		);
	}

	/**
	 * This test will change when the serialization format changes.
	 * If it is being changed intentionally, the test should be updated.
	 * It is just here to catch unintentional changes.
	 */
	public function testSerializationStability() {
		$serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":4:{Q123}';
		$id = new ItemId( 'q123' );

		$this->assertSame(
			serialize( $id ),
			$serialization
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testReturnTypeOfToString( EntityId $id ) {
		$this->assertIsString( $id->__toString() );
	}

	public function testIsForeign() {
		$this->assertFalse( ( new ItemId( 'Q42' ) )->isForeign() );
		$this->assertFalse( ( new ItemId( ':Q42' ) )->isForeign() );
		$this->assertTrue( ( new ItemId( 'foo:Q42' ) )->isForeign() );
		$this->assertFalse( ( new NumericPropertyId( ':P42' ) )->isForeign() );
		$this->assertTrue( ( new NumericPropertyId( 'foo:P42' ) )->isForeign() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetRepositoryName( EntityId $id, $repoName ) {
		$this->assertSame( $repoName, $id->getRepositoryName() );
	}

	public function serializationSplitProvider() {
		return [
			[ 'Q42', [ '', '', 'Q42' ] ],
			[ 'foo:Q42', [ 'foo', '', 'Q42' ] ],
			[ '0:Q42', [ '0', '', 'Q42' ] ],
			[ 'foo:bar:baz:Q42', [ 'foo', 'bar:baz', 'Q42' ] ],
		];
	}

	/**
	 * @dataProvider serializationSplitProvider
	 */
	public function testSplitSerialization( $serialization, $split ) {
		$this->assertSame( $split, EntityId::splitSerialization( $serialization ) );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testSplitSerializationFails_GivenInvalidSerialization( $serialization ) {
		$this->expectException( InvalidArgumentException::class );
		EntityId::splitSerialization( $serialization );
	}

	/**
	 * @dataProvider serializationSplitProvider
	 */
	public function testJoinSerialization( $serialization, $split ) {
		$this->assertSame( $serialization, EntityId::joinSerialization( $split ) );
	}

	/**
	 * @dataProvider invalidJoinSerializationDataProvider
	 */
	public function testJoinSerializationFails_GivenEmptyId( $parts ) {
		$this->expectException( InvalidArgumentException::class );
		EntityId::joinSerialization( $parts );
	}

	public function invalidJoinSerializationDataProvider() {
		return [
			[ [ 'Q42', '', '' ] ],
			[ [ '', 'Q42', '' ] ],
			[ [ 'foo', 'Q42', '' ] ],
		];
	}

	public function testGivenNotNormalizedSerialization_splitSerializationReturnsNormalizedParts() {
		$this->assertSame( [ '', '', 'Q42' ], EntityId::splitSerialization( ':Q42' ) );
		$this->assertSame( [ 'foo', 'bar', 'Q42' ], EntityId::splitSerialization( ':foo:bar:Q42' ) );
	}

	public function localPartDataProvider() {
		return [
			[ 'Q42', 'Q42' ],
			[ ':Q42', 'Q42' ],
			[ 'foo:Q42', 'Q42' ],
			[ 'foo:bar:Q42', 'bar:Q42' ],
		];
	}

	/**
	 * @dataProvider localPartDataProvider
	 */
	public function testGetLocalPart( $serialization, $localPart ) {
		$id = new ItemId( $serialization );
		$this->assertSame( $localPart, $id->getLocalPart() );
	}

	public function invalidSerializationProvider() {
		return [
			[ 's p a c e s:Q42' ],
			[ '::Q42' ],
			[ '' ],
			[ ':' ],
			[ 42 ],
			[ null ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testConstructor( $serialization ) {
		$mock = $this->createMock( EntityId::class );

		$constructor = ( new ReflectionClass( EntityId::class ) )->getConstructor();

		$this->expectException( InvalidArgumentException::class );
		$constructor->invoke( $mock, $serialization );
	}

}

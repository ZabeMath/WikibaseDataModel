<?php
declare( strict_types=1 );

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class NumericPropertyId extends PropertyId {

	public const PATTERN = '/^P[1-9]\d{0,9}\z/i';

	/**
	 * @param string $idSerialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $idSerialization ) {
		$parts = self::splitSerialization( $idSerialization );
		$this->assertValidIdFormat( $parts[2] );
		parent::__construct( self::joinSerialization(
			[ $parts[0], $parts[1], strtoupper( $parts[2] ) ]
		) );
	}

	private function assertValidIdFormat( $idSerialization ) {
		if ( !preg_match( self::PATTERN, $idSerialization ) ) {
			throw new InvalidArgumentException( '$idSerialization must match ' . self::PATTERN );
		}

		if ( strlen( $idSerialization ) > 10
			&& substr( $idSerialization, 1 ) > Int32EntityId::MAX
		) {
			throw new InvalidArgumentException( '$idSerialization can not exceed '
				. Int32EntityId::MAX );
		}
	}

	/**
	 * @see Int32EntityId::getNumericId
	 *
	 * @return int Guaranteed to be a distinct integer in the range [1..2147483647].
	 */
	public function getNumericId() {
		$serializationParts = self::splitSerialization( $this->serialization );
		return (int)substr( $serializationParts[2], 1 );
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'property';
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$array = json_decode( $serialized );
		$this->serialization = is_array( $array ) ? $array[1] : $serialized;
		list( $this->repositoryName, $this->localPart ) =
			self::extractRepositoryNameAndLocalPart( $this->serialization );
	}

	/**
	 * Construct a NumericPropertyId given the numeric part of its serialization.
	 *
	 * CAUTION: new usages of this method are discouraged. Typically you
	 * should avoid dealing with just the numeric part, and use the whole
	 * serialization. Not doing so in new code requires special justification.
	 *
	 * @param int|float|string $numericId
	 *
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function newFromNumber( $numericId ) {
		if ( !is_numeric( $numericId ) ) {
			throw new InvalidArgumentException( '$numericId must be numeric' );
		}

		return new self( 'P' . $numericId );
	}

	/**
	 * CAUTION: Use the full string serialization whenever you can and avoid using numeric IDs.
	 *
	 * @param string $repositoryName
	 * @param int|float|string $numericId
	 *
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function newFromRepositoryAndNumber( $repositoryName, $numericId ) {
		if ( !is_numeric( $numericId ) ) {
			throw new InvalidArgumentException( '$numericId must be numeric' );
		}

		return new self( self::joinSerialization( [ $repositoryName, '', 'P' . $numericId ] ) );
	}
}

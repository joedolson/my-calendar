<?php
/**
 * Extend Spatie event specification to support additional fields.
 *
 * @package   Spatie/icalendar-generator
 * @author    Joe Dolson
 * @link      https://www.joedolson.com
 * @copyright 2024 Joe Dolson
 * @license   GPL-2.0-or-later
 * @version   1.0.0
 */

namespace Spatie\IcalendarGenerator\Components;

use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\ComponentPayload;
use Spatie\IcalendarGenerator\Properties\TextProperty;

/**
 * Custom iCal event fields.
 *
 * Adds additional fields to the Spatie iCal event format.
 *
 * @package Spatie\IcalendarGenerator
 * @author  Joe Dolson
 */
class CustomEvent extends Event {
	/**
	 * Create new event type.
	 *
	 * @param string $name Event title.
	 *
	 * @since 1.0.0
	 */
	public static function create( string $name = null ): CustomEvent {
		return new self( $name );
	}

	/**
	 * HTML description holder.
	 *
	 * @var string
	 */
	public ?string $description_html = null;
	/**
	 * Categories.
	 *
	 * @var array
	 */
	public ?array $categories = null;
	/**
	 * Event URL.
	 *
	 * @var string
	 */
	public ?string $url = null;

	/**
	 * Add HTML description to the object.
	 *
	 * @param string $description_html Description content.
	 *
	 * @since 1.0.0
	 */
	public function description_html( string $description_html ): CustomEvent {
		$this->description_html = $description_html;
		return $this;
	}

	/**
	 * Add array of category names to the object.
	 *
	 * @param array $categories Array of string category names.
	 *
	 * @since 1.0.0
	 */
	public function categories( array $categories ): CustomEvent {
		$this->categories = $categories;
		return $this;
	}

	/**
	 * Add URL to the event object.
	 *
	 * @param string $url Url link to event.
	 *
	 * @since 1.0.0
	 */
	public function url( string $url ): CustomEvent {
		$this->url = $url;
		return $this;
	}

	/**
	 * Handle custom properties and add to output payload.
	 *
	 * @since 1.0.0
	 */
	protected function payload(): ComponentPayload {
		$payload = parent::payload();
		$this->resolveCustomProperties( $payload );
		return $payload;
	}

	/**
	 * Add custom properties to payload.
	 *
	 * @param ComponentPayload $payload Event object.
	 *
	 * @since 1.0.0
	 */
	private function resolveCustomProperties( ComponentPayload $payload ): self {
		$payload
			->optional(
				$this->description_html,
				fn () => TextProperty::create( 'X-ALT-DESC;FMTTYPE=TEXT/HTML', $this->description_html )
			)
			->optional(
				$this->categories,
				fn () => TextProperty::create( 'CATEGORIES', implode( ', ', $this->categories ) )->withoutEscaping()
			)
			->optional(
				$this->url,
				fn () => TextProperty::create( 'URL;VALUE=URI', $this->url )->withoutEscaping()
			);

		return $this;
	}
}

<?php
namespace Wealcoder\AnimationAddons\INC\WPML;

defined( 'ABSPATH' ) || die();

use WPML_PB_String;
use IWPML_Page_Builders_Module;
use WPML_Elementor_Translatable_Nodes;

/**
 * Class WPML_Module_With_Items
 */
abstract class WPML_Module_With_Items implements IWPML_Page_Builders_Module {

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	abstract protected function get_title( $field );

	/** @return array */
	abstract protected function get_fields();

	/**
	 * @param string $field
	 *
	 * @return string mixed
	 */
	abstract protected function get_editor_type( $field );

	/**
	 * @return string
	 */
	abstract public function get_items_field();

	/**
	 * @param string|int $node_id
	 * @param array $element
	 * @param WPML_PB_String[] $strings
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $element, $strings ) {
		foreach ( $this->get_items( $element ) as $item ) {
			foreach( $this->get_fields() as $key => $field ) {
				if ( ! is_array( $field ) ) {

					if ( ! isset( $item[ $field ] ) ) {
						continue;
					}

					$strings[] = new WPML_PB_String(
						$item[ $field ],
						$this->get_string_name( $node_id, $item[ $field ], $field, $element['widgetType'], $item['_id'] ),
						$this->get_title( $field ),
						$this->get_editor_type( $field )
					);
				} else {
					foreach ( $field as $inner_field ) {

						if ( ! isset( $item[ $key ][ $inner_field ] ) ) {
							continue;
						}

						$strings[] = new WPML_PB_String(
							$item[ $key ][ $inner_field ],
							$this->get_string_name( $node_id, $item[ $key ][ $inner_field ], $key . '_' . $inner_field, $element['widgetType'], $item['_id'] ),
							$this->get_title( $key ),
							$this->get_editor_type( $key )
						);
					}
				}
			}
		}
		return $strings;
	}

	/**
	 * @param int|string $node_id
	 * @param mixed $element
	 * @param WPML_PB_String $string
	 *
	 * @return mixed
	 */
	public function update( $node_id, $element, WPML_PB_String $string ) {
		foreach ( $this->get_items( $element ) as $key => $item ) {
			foreach( $this->get_fields() as $field_key => $field ) {
				if ( ! is_array( $field ) ) {

					if ( ! isset( $item[ $field ] ) ) {
						continue;
					}

					if ( $this->get_string_name( $node_id, $item[ $field ], $field, $element['widgetType'], $item['_id'] ) === $string->get_name() ) {
						$item[ $field ] = $string->get_value();
						$item['index'] = $key;
						return $item;
					}
				} else {
					foreach ( $field as $inner_field ) {
						if ( ! isset( $item[ $field_key ][ $inner_field ] ) ) {
							continue;
						}

						if ( $this->get_string_name( $node_id, $item[ $field_key ][ $inner_field ], $field_key . '_' . $inner_field, $element['widgetType'], $item['_id'] ) === $string->get_name() ) {
							$item[ $field_key ][ $inner_field ] = $string->get_value();
							$item['index'] = $key;
							return $item;
						}
					}
				}
			}
		}
	}

	/**
	 * @param string $node_id
	 * @param string $value
	 * @param string $type
	 * @param string $key
	 * @param string $item_id
	 *
	 * @return string
	 */
	private function get_string_name( $node_id, $value, $type, $key = '', $item_id = '' ) {
		return $key . '-' . $type . '-' . $node_id . '-' . $item_id;
	}

	/**
	 * @param $element
	 *
	 * @return mixed
	 */
	public function get_items( $element ) {
		$items = $element[ WPML_Elementor_Translatable_Nodes::SETTINGS_FIELD ][ $this->get_items_field() ] ?? array();

		return is_array( $items ) ? $items : array();
	}

}

/**
 * A repeater the saved element does not carry — a page saved before the control
 * existed, or a section that was never opened — is simply absent from its
 * settings. WPML's own get_items() reads it unguarded and then foreach()es the
 * result, so every such element logged two warnings per save, which land inside
 * the editor's ajax JSON wherever display_errors is on.
 */
trait Aaeaddon_Wpml_Safe_Items {

	/**
	 * @param array $element
	 *
	 * @return array
	 */
	public function get_items( $element ) {
		$items = $element['settings'][ $this->get_items_field() ] ?? array();

		return is_array( $items ) ? $items : array();
	}
}

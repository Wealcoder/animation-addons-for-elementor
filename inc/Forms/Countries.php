<?php
/**
 * AAE Forms — canonical country list (ISO 3166-1 alpha-2).
 *
 * Single source of truth for the Country form field: the widget's default
 * `options` prop, the Schema_Walker's schema-snapshot fallback and (through
 * the schema) the Validator's server-side whitelist all read from here, so
 * the three can never drift apart.
 *
 * Names are translatable; the whole list is filterable via
 * `aae_form/countries` (add/remove/rename entries — keys must stay ISO
 * alpha-2 style codes, they are what gets submitted and stored).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Countries {

	/**
	 * code => translated name, filterable.
	 *
	 * @return array<string,string>
	 */
	public static function all(): array {
		$countries = [
			'AF' => __( 'Afghanistan', 'animation-addons-for-elementor' ),
			'AX' => __( 'Åland Islands', 'animation-addons-for-elementor' ),
			'AL' => __( 'Albania', 'animation-addons-for-elementor' ),
			'DZ' => __( 'Algeria', 'animation-addons-for-elementor' ),
			'AS' => __( 'American Samoa', 'animation-addons-for-elementor' ),
			'AD' => __( 'Andorra', 'animation-addons-for-elementor' ),
			'AO' => __( 'Angola', 'animation-addons-for-elementor' ),
			'AI' => __( 'Anguilla', 'animation-addons-for-elementor' ),
			'AG' => __( 'Antigua and Barbuda', 'animation-addons-for-elementor' ),
			'AR' => __( 'Argentina', 'animation-addons-for-elementor' ),
			'AM' => __( 'Armenia', 'animation-addons-for-elementor' ),
			'AW' => __( 'Aruba', 'animation-addons-for-elementor' ),
			'AU' => __( 'Australia', 'animation-addons-for-elementor' ),
			'AT' => __( 'Austria', 'animation-addons-for-elementor' ),
			'AZ' => __( 'Azerbaijan', 'animation-addons-for-elementor' ),
			'BS' => __( 'Bahamas', 'animation-addons-for-elementor' ),
			'BH' => __( 'Bahrain', 'animation-addons-for-elementor' ),
			'BD' => __( 'Bangladesh', 'animation-addons-for-elementor' ),
			'BB' => __( 'Barbados', 'animation-addons-for-elementor' ),
			'BY' => __( 'Belarus', 'animation-addons-for-elementor' ),
			'BE' => __( 'Belgium', 'animation-addons-for-elementor' ),
			'BZ' => __( 'Belize', 'animation-addons-for-elementor' ),
			'BJ' => __( 'Benin', 'animation-addons-for-elementor' ),
			'BM' => __( 'Bermuda', 'animation-addons-for-elementor' ),
			'BT' => __( 'Bhutan', 'animation-addons-for-elementor' ),
			'BO' => __( 'Bolivia', 'animation-addons-for-elementor' ),
			'BA' => __( 'Bosnia and Herzegovina', 'animation-addons-for-elementor' ),
			'BW' => __( 'Botswana', 'animation-addons-for-elementor' ),
			'BR' => __( 'Brazil', 'animation-addons-for-elementor' ),
			'IO' => __( 'British Indian Ocean Territory', 'animation-addons-for-elementor' ),
			'VG' => __( 'British Virgin Islands', 'animation-addons-for-elementor' ),
			'BN' => __( 'Brunei', 'animation-addons-for-elementor' ),
			'BG' => __( 'Bulgaria', 'animation-addons-for-elementor' ),
			'BF' => __( 'Burkina Faso', 'animation-addons-for-elementor' ),
			'BI' => __( 'Burundi', 'animation-addons-for-elementor' ),
			'KH' => __( 'Cambodia', 'animation-addons-for-elementor' ),
			'CM' => __( 'Cameroon', 'animation-addons-for-elementor' ),
			'CA' => __( 'Canada', 'animation-addons-for-elementor' ),
			'CV' => __( 'Cape Verde', 'animation-addons-for-elementor' ),
			'KY' => __( 'Cayman Islands', 'animation-addons-for-elementor' ),
			'CF' => __( 'Central African Republic', 'animation-addons-for-elementor' ),
			'TD' => __( 'Chad', 'animation-addons-for-elementor' ),
			'CL' => __( 'Chile', 'animation-addons-for-elementor' ),
			'CN' => __( 'China', 'animation-addons-for-elementor' ),
			'CO' => __( 'Colombia', 'animation-addons-for-elementor' ),
			'KM' => __( 'Comoros', 'animation-addons-for-elementor' ),
			'CG' => __( 'Congo', 'animation-addons-for-elementor' ),
			'CD' => __( 'Congo (DRC)', 'animation-addons-for-elementor' ),
			'CK' => __( 'Cook Islands', 'animation-addons-for-elementor' ),
			'CR' => __( 'Costa Rica', 'animation-addons-for-elementor' ),
			'CI' => __( 'Côte d’Ivoire', 'animation-addons-for-elementor' ),
			'HR' => __( 'Croatia', 'animation-addons-for-elementor' ),
			'CU' => __( 'Cuba', 'animation-addons-for-elementor' ),
			'CW' => __( 'Curaçao', 'animation-addons-for-elementor' ),
			'CY' => __( 'Cyprus', 'animation-addons-for-elementor' ),
			'CZ' => __( 'Czechia', 'animation-addons-for-elementor' ),
			'DK' => __( 'Denmark', 'animation-addons-for-elementor' ),
			'DJ' => __( 'Djibouti', 'animation-addons-for-elementor' ),
			'DM' => __( 'Dominica', 'animation-addons-for-elementor' ),
			'DO' => __( 'Dominican Republic', 'animation-addons-for-elementor' ),
			'EC' => __( 'Ecuador', 'animation-addons-for-elementor' ),
			'EG' => __( 'Egypt', 'animation-addons-for-elementor' ),
			'SV' => __( 'El Salvador', 'animation-addons-for-elementor' ),
			'GQ' => __( 'Equatorial Guinea', 'animation-addons-for-elementor' ),
			'ER' => __( 'Eritrea', 'animation-addons-for-elementor' ),
			'EE' => __( 'Estonia', 'animation-addons-for-elementor' ),
			'SZ' => __( 'Eswatini', 'animation-addons-for-elementor' ),
			'ET' => __( 'Ethiopia', 'animation-addons-for-elementor' ),
			'FK' => __( 'Falkland Islands', 'animation-addons-for-elementor' ),
			'FO' => __( 'Faroe Islands', 'animation-addons-for-elementor' ),
			'FJ' => __( 'Fiji', 'animation-addons-for-elementor' ),
			'FI' => __( 'Finland', 'animation-addons-for-elementor' ),
			'FR' => __( 'France', 'animation-addons-for-elementor' ),
			'GF' => __( 'French Guiana', 'animation-addons-for-elementor' ),
			'PF' => __( 'French Polynesia', 'animation-addons-for-elementor' ),
			'GA' => __( 'Gabon', 'animation-addons-for-elementor' ),
			'GM' => __( 'Gambia', 'animation-addons-for-elementor' ),
			'GE' => __( 'Georgia', 'animation-addons-for-elementor' ),
			'DE' => __( 'Germany', 'animation-addons-for-elementor' ),
			'GH' => __( 'Ghana', 'animation-addons-for-elementor' ),
			'GI' => __( 'Gibraltar', 'animation-addons-for-elementor' ),
			'GR' => __( 'Greece', 'animation-addons-for-elementor' ),
			'GL' => __( 'Greenland', 'animation-addons-for-elementor' ),
			'GD' => __( 'Grenada', 'animation-addons-for-elementor' ),
			'GP' => __( 'Guadeloupe', 'animation-addons-for-elementor' ),
			'GU' => __( 'Guam', 'animation-addons-for-elementor' ),
			'GT' => __( 'Guatemala', 'animation-addons-for-elementor' ),
			'GG' => __( 'Guernsey', 'animation-addons-for-elementor' ),
			'GN' => __( 'Guinea', 'animation-addons-for-elementor' ),
			'GW' => __( 'Guinea-Bissau', 'animation-addons-for-elementor' ),
			'GY' => __( 'Guyana', 'animation-addons-for-elementor' ),
			'HT' => __( 'Haiti', 'animation-addons-for-elementor' ),
			'HN' => __( 'Honduras', 'animation-addons-for-elementor' ),
			'HK' => __( 'Hong Kong', 'animation-addons-for-elementor' ),
			'HU' => __( 'Hungary', 'animation-addons-for-elementor' ),
			'IS' => __( 'Iceland', 'animation-addons-for-elementor' ),
			'IN' => __( 'India', 'animation-addons-for-elementor' ),
			'ID' => __( 'Indonesia', 'animation-addons-for-elementor' ),
			'IR' => __( 'Iran', 'animation-addons-for-elementor' ),
			'IQ' => __( 'Iraq', 'animation-addons-for-elementor' ),
			'IE' => __( 'Ireland', 'animation-addons-for-elementor' ),
			'IM' => __( 'Isle of Man', 'animation-addons-for-elementor' ),
			'IL' => __( 'Israel', 'animation-addons-for-elementor' ),
			'IT' => __( 'Italy', 'animation-addons-for-elementor' ),
			'JM' => __( 'Jamaica', 'animation-addons-for-elementor' ),
			'JP' => __( 'Japan', 'animation-addons-for-elementor' ),
			'JE' => __( 'Jersey', 'animation-addons-for-elementor' ),
			'JO' => __( 'Jordan', 'animation-addons-for-elementor' ),
			'KZ' => __( 'Kazakhstan', 'animation-addons-for-elementor' ),
			'KE' => __( 'Kenya', 'animation-addons-for-elementor' ),
			'KI' => __( 'Kiribati', 'animation-addons-for-elementor' ),
			'KW' => __( 'Kuwait', 'animation-addons-for-elementor' ),
			'KG' => __( 'Kyrgyzstan', 'animation-addons-for-elementor' ),
			'LA' => __( 'Laos', 'animation-addons-for-elementor' ),
			'LV' => __( 'Latvia', 'animation-addons-for-elementor' ),
			'LB' => __( 'Lebanon', 'animation-addons-for-elementor' ),
			'LS' => __( 'Lesotho', 'animation-addons-for-elementor' ),
			'LR' => __( 'Liberia', 'animation-addons-for-elementor' ),
			'LY' => __( 'Libya', 'animation-addons-for-elementor' ),
			'LI' => __( 'Liechtenstein', 'animation-addons-for-elementor' ),
			'LT' => __( 'Lithuania', 'animation-addons-for-elementor' ),
			'LU' => __( 'Luxembourg', 'animation-addons-for-elementor' ),
			'MO' => __( 'Macao', 'animation-addons-for-elementor' ),
			'MG' => __( 'Madagascar', 'animation-addons-for-elementor' ),
			'MW' => __( 'Malawi', 'animation-addons-for-elementor' ),
			'MY' => __( 'Malaysia', 'animation-addons-for-elementor' ),
			'MV' => __( 'Maldives', 'animation-addons-for-elementor' ),
			'ML' => __( 'Mali', 'animation-addons-for-elementor' ),
			'MT' => __( 'Malta', 'animation-addons-for-elementor' ),
			'MH' => __( 'Marshall Islands', 'animation-addons-for-elementor' ),
			'MQ' => __( 'Martinique', 'animation-addons-for-elementor' ),
			'MR' => __( 'Mauritania', 'animation-addons-for-elementor' ),
			'MU' => __( 'Mauritius', 'animation-addons-for-elementor' ),
			'YT' => __( 'Mayotte', 'animation-addons-for-elementor' ),
			'MX' => __( 'Mexico', 'animation-addons-for-elementor' ),
			'FM' => __( 'Micronesia', 'animation-addons-for-elementor' ),
			'MD' => __( 'Moldova', 'animation-addons-for-elementor' ),
			'MC' => __( 'Monaco', 'animation-addons-for-elementor' ),
			'MN' => __( 'Mongolia', 'animation-addons-for-elementor' ),
			'ME' => __( 'Montenegro', 'animation-addons-for-elementor' ),
			'MS' => __( 'Montserrat', 'animation-addons-for-elementor' ),
			'MA' => __( 'Morocco', 'animation-addons-for-elementor' ),
			'MZ' => __( 'Mozambique', 'animation-addons-for-elementor' ),
			'MM' => __( 'Myanmar', 'animation-addons-for-elementor' ),
			'NA' => __( 'Namibia', 'animation-addons-for-elementor' ),
			'NR' => __( 'Nauru', 'animation-addons-for-elementor' ),
			'NP' => __( 'Nepal', 'animation-addons-for-elementor' ),
			'NL' => __( 'Netherlands', 'animation-addons-for-elementor' ),
			'NC' => __( 'New Caledonia', 'animation-addons-for-elementor' ),
			'NZ' => __( 'New Zealand', 'animation-addons-for-elementor' ),
			'NI' => __( 'Nicaragua', 'animation-addons-for-elementor' ),
			'NE' => __( 'Niger', 'animation-addons-for-elementor' ),
			'NG' => __( 'Nigeria', 'animation-addons-for-elementor' ),
			'NU' => __( 'Niue', 'animation-addons-for-elementor' ),
			'NF' => __( 'Norfolk Island', 'animation-addons-for-elementor' ),
			'KP' => __( 'North Korea', 'animation-addons-for-elementor' ),
			'MK' => __( 'North Macedonia', 'animation-addons-for-elementor' ),
			'MP' => __( 'Northern Mariana Islands', 'animation-addons-for-elementor' ),
			'NO' => __( 'Norway', 'animation-addons-for-elementor' ),
			'OM' => __( 'Oman', 'animation-addons-for-elementor' ),
			'PK' => __( 'Pakistan', 'animation-addons-for-elementor' ),
			'PW' => __( 'Palau', 'animation-addons-for-elementor' ),
			'PS' => __( 'Palestine', 'animation-addons-for-elementor' ),
			'PA' => __( 'Panama', 'animation-addons-for-elementor' ),
			'PG' => __( 'Papua New Guinea', 'animation-addons-for-elementor' ),
			'PY' => __( 'Paraguay', 'animation-addons-for-elementor' ),
			'PE' => __( 'Peru', 'animation-addons-for-elementor' ),
			'PH' => __( 'Philippines', 'animation-addons-for-elementor' ),
			'PN' => __( 'Pitcairn Islands', 'animation-addons-for-elementor' ),
			'PL' => __( 'Poland', 'animation-addons-for-elementor' ),
			'PT' => __( 'Portugal', 'animation-addons-for-elementor' ),
			'PR' => __( 'Puerto Rico', 'animation-addons-for-elementor' ),
			'QA' => __( 'Qatar', 'animation-addons-for-elementor' ),
			'RE' => __( 'Réunion', 'animation-addons-for-elementor' ),
			'RO' => __( 'Romania', 'animation-addons-for-elementor' ),
			'RU' => __( 'Russia', 'animation-addons-for-elementor' ),
			'RW' => __( 'Rwanda', 'animation-addons-for-elementor' ),
			'BL' => __( 'Saint Barthélemy', 'animation-addons-for-elementor' ),
			'SH' => __( 'Saint Helena', 'animation-addons-for-elementor' ),
			'KN' => __( 'Saint Kitts and Nevis', 'animation-addons-for-elementor' ),
			'LC' => __( 'Saint Lucia', 'animation-addons-for-elementor' ),
			'MF' => __( 'Saint Martin', 'animation-addons-for-elementor' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'animation-addons-for-elementor' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'animation-addons-for-elementor' ),
			'WS' => __( 'Samoa', 'animation-addons-for-elementor' ),
			'SM' => __( 'San Marino', 'animation-addons-for-elementor' ),
			'ST' => __( 'São Tomé and Príncipe', 'animation-addons-for-elementor' ),
			'SA' => __( 'Saudi Arabia', 'animation-addons-for-elementor' ),
			'SN' => __( 'Senegal', 'animation-addons-for-elementor' ),
			'RS' => __( 'Serbia', 'animation-addons-for-elementor' ),
			'SC' => __( 'Seychelles', 'animation-addons-for-elementor' ),
			'SL' => __( 'Sierra Leone', 'animation-addons-for-elementor' ),
			'SG' => __( 'Singapore', 'animation-addons-for-elementor' ),
			'SX' => __( 'Sint Maarten', 'animation-addons-for-elementor' ),
			'SK' => __( 'Slovakia', 'animation-addons-for-elementor' ),
			'SI' => __( 'Slovenia', 'animation-addons-for-elementor' ),
			'SB' => __( 'Solomon Islands', 'animation-addons-for-elementor' ),
			'SO' => __( 'Somalia', 'animation-addons-for-elementor' ),
			'ZA' => __( 'South Africa', 'animation-addons-for-elementor' ),
			'KR' => __( 'South Korea', 'animation-addons-for-elementor' ),
			'SS' => __( 'South Sudan', 'animation-addons-for-elementor' ),
			'ES' => __( 'Spain', 'animation-addons-for-elementor' ),
			'LK' => __( 'Sri Lanka', 'animation-addons-for-elementor' ),
			'SD' => __( 'Sudan', 'animation-addons-for-elementor' ),
			'SR' => __( 'Suriname', 'animation-addons-for-elementor' ),
			'SE' => __( 'Sweden', 'animation-addons-for-elementor' ),
			'CH' => __( 'Switzerland', 'animation-addons-for-elementor' ),
			'SY' => __( 'Syria', 'animation-addons-for-elementor' ),
			'TW' => __( 'Taiwan', 'animation-addons-for-elementor' ),
			'TJ' => __( 'Tajikistan', 'animation-addons-for-elementor' ),
			'TZ' => __( 'Tanzania', 'animation-addons-for-elementor' ),
			'TH' => __( 'Thailand', 'animation-addons-for-elementor' ),
			'TL' => __( 'Timor-Leste', 'animation-addons-for-elementor' ),
			'TG' => __( 'Togo', 'animation-addons-for-elementor' ),
			'TK' => __( 'Tokelau', 'animation-addons-for-elementor' ),
			'TO' => __( 'Tonga', 'animation-addons-for-elementor' ),
			'TT' => __( 'Trinidad and Tobago', 'animation-addons-for-elementor' ),
			'TN' => __( 'Tunisia', 'animation-addons-for-elementor' ),
			'TR' => __( 'Türkiye', 'animation-addons-for-elementor' ),
			'TM' => __( 'Turkmenistan', 'animation-addons-for-elementor' ),
			'TC' => __( 'Turks and Caicos Islands', 'animation-addons-for-elementor' ),
			'TV' => __( 'Tuvalu', 'animation-addons-for-elementor' ),
			'UG' => __( 'Uganda', 'animation-addons-for-elementor' ),
			'UA' => __( 'Ukraine', 'animation-addons-for-elementor' ),
			'AE' => __( 'United Arab Emirates', 'animation-addons-for-elementor' ),
			'GB' => __( 'United Kingdom', 'animation-addons-for-elementor' ),
			'US' => __( 'United States', 'animation-addons-for-elementor' ),
			'VI' => __( 'U.S. Virgin Islands', 'animation-addons-for-elementor' ),
			'UY' => __( 'Uruguay', 'animation-addons-for-elementor' ),
			'UZ' => __( 'Uzbekistan', 'animation-addons-for-elementor' ),
			'VU' => __( 'Vanuatu', 'animation-addons-for-elementor' ),
			'VA' => __( 'Vatican City', 'animation-addons-for-elementor' ),
			'VE' => __( 'Venezuela', 'animation-addons-for-elementor' ),
			'VN' => __( 'Vietnam', 'animation-addons-for-elementor' ),
			'WF' => __( 'Wallis and Futuna', 'animation-addons-for-elementor' ),
			'EH' => __( 'Western Sahara', 'animation-addons-for-elementor' ),
			'YE' => __( 'Yemen', 'animation-addons-for-elementor' ),
			'ZM' => __( 'Zambia', 'animation-addons-for-elementor' ),
			'ZW' => __( 'Zimbabwe', 'animation-addons-for-elementor' ),
		];

		/**
		 * Customize the Country field's list — add, remove, rename or reorder
		 * entries (array order is the rendered order). Keys are the submitted
		 * values; keep them short and stable (ISO alpha-2 style).
		 *
		 * @param array<string,string> $countries code => label.
		 */
		return apply_filters( 'aae_form/countries', $countries );
	}

	/**
	 * The list in the Select widget family's "value|Label" one-per-line
	 * format — used as the Country widget's `options` prop default and as
	 * the Schema_Walker's fallback for untouched fields, so the server-side
	 * whitelist always matches what the twig rendered.
	 */
	public static function options_string(): string {
		$lines = [];

		foreach ( self::all() as $code => $label ) {
			// "|" would break the line format; labels never contain it, but a
			// filtered entry might — strip it defensively.
			$lines[] = str_replace( '|', '', (string) $code ) . '|' . str_replace( '|', '', (string) $label );
		}

		return implode( "\n", $lines );
	}
}

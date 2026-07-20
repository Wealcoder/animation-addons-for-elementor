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

	private const TD = 'animation-addons-for-elementor';

	/**
	 * code => translated name, filterable.
	 *
	 * @return array<string,string>
	 */
	public static function all(): array {
		$countries = [
			'AF' => __( 'Afghanistan', self::TD ),
			'AX' => __( 'Åland Islands', self::TD ),
			'AL' => __( 'Albania', self::TD ),
			'DZ' => __( 'Algeria', self::TD ),
			'AS' => __( 'American Samoa', self::TD ),
			'AD' => __( 'Andorra', self::TD ),
			'AO' => __( 'Angola', self::TD ),
			'AI' => __( 'Anguilla', self::TD ),
			'AG' => __( 'Antigua and Barbuda', self::TD ),
			'AR' => __( 'Argentina', self::TD ),
			'AM' => __( 'Armenia', self::TD ),
			'AW' => __( 'Aruba', self::TD ),
			'AU' => __( 'Australia', self::TD ),
			'AT' => __( 'Austria', self::TD ),
			'AZ' => __( 'Azerbaijan', self::TD ),
			'BS' => __( 'Bahamas', self::TD ),
			'BH' => __( 'Bahrain', self::TD ),
			'BD' => __( 'Bangladesh', self::TD ),
			'BB' => __( 'Barbados', self::TD ),
			'BY' => __( 'Belarus', self::TD ),
			'BE' => __( 'Belgium', self::TD ),
			'BZ' => __( 'Belize', self::TD ),
			'BJ' => __( 'Benin', self::TD ),
			'BM' => __( 'Bermuda', self::TD ),
			'BT' => __( 'Bhutan', self::TD ),
			'BO' => __( 'Bolivia', self::TD ),
			'BA' => __( 'Bosnia and Herzegovina', self::TD ),
			'BW' => __( 'Botswana', self::TD ),
			'BR' => __( 'Brazil', self::TD ),
			'IO' => __( 'British Indian Ocean Territory', self::TD ),
			'VG' => __( 'British Virgin Islands', self::TD ),
			'BN' => __( 'Brunei', self::TD ),
			'BG' => __( 'Bulgaria', self::TD ),
			'BF' => __( 'Burkina Faso', self::TD ),
			'BI' => __( 'Burundi', self::TD ),
			'KH' => __( 'Cambodia', self::TD ),
			'CM' => __( 'Cameroon', self::TD ),
			'CA' => __( 'Canada', self::TD ),
			'CV' => __( 'Cape Verde', self::TD ),
			'KY' => __( 'Cayman Islands', self::TD ),
			'CF' => __( 'Central African Republic', self::TD ),
			'TD' => __( 'Chad', self::TD ),
			'CL' => __( 'Chile', self::TD ),
			'CN' => __( 'China', self::TD ),
			'CO' => __( 'Colombia', self::TD ),
			'KM' => __( 'Comoros', self::TD ),
			'CG' => __( 'Congo', self::TD ),
			'CD' => __( 'Congo (DRC)', self::TD ),
			'CK' => __( 'Cook Islands', self::TD ),
			'CR' => __( 'Costa Rica', self::TD ),
			'CI' => __( 'Côte d’Ivoire', self::TD ),
			'HR' => __( 'Croatia', self::TD ),
			'CU' => __( 'Cuba', self::TD ),
			'CW' => __( 'Curaçao', self::TD ),
			'CY' => __( 'Cyprus', self::TD ),
			'CZ' => __( 'Czechia', self::TD ),
			'DK' => __( 'Denmark', self::TD ),
			'DJ' => __( 'Djibouti', self::TD ),
			'DM' => __( 'Dominica', self::TD ),
			'DO' => __( 'Dominican Republic', self::TD ),
			'EC' => __( 'Ecuador', self::TD ),
			'EG' => __( 'Egypt', self::TD ),
			'SV' => __( 'El Salvador', self::TD ),
			'GQ' => __( 'Equatorial Guinea', self::TD ),
			'ER' => __( 'Eritrea', self::TD ),
			'EE' => __( 'Estonia', self::TD ),
			'SZ' => __( 'Eswatini', self::TD ),
			'ET' => __( 'Ethiopia', self::TD ),
			'FK' => __( 'Falkland Islands', self::TD ),
			'FO' => __( 'Faroe Islands', self::TD ),
			'FJ' => __( 'Fiji', self::TD ),
			'FI' => __( 'Finland', self::TD ),
			'FR' => __( 'France', self::TD ),
			'GF' => __( 'French Guiana', self::TD ),
			'PF' => __( 'French Polynesia', self::TD ),
			'GA' => __( 'Gabon', self::TD ),
			'GM' => __( 'Gambia', self::TD ),
			'GE' => __( 'Georgia', self::TD ),
			'DE' => __( 'Germany', self::TD ),
			'GH' => __( 'Ghana', self::TD ),
			'GI' => __( 'Gibraltar', self::TD ),
			'GR' => __( 'Greece', self::TD ),
			'GL' => __( 'Greenland', self::TD ),
			'GD' => __( 'Grenada', self::TD ),
			'GP' => __( 'Guadeloupe', self::TD ),
			'GU' => __( 'Guam', self::TD ),
			'GT' => __( 'Guatemala', self::TD ),
			'GG' => __( 'Guernsey', self::TD ),
			'GN' => __( 'Guinea', self::TD ),
			'GW' => __( 'Guinea-Bissau', self::TD ),
			'GY' => __( 'Guyana', self::TD ),
			'HT' => __( 'Haiti', self::TD ),
			'HN' => __( 'Honduras', self::TD ),
			'HK' => __( 'Hong Kong', self::TD ),
			'HU' => __( 'Hungary', self::TD ),
			'IS' => __( 'Iceland', self::TD ),
			'IN' => __( 'India', self::TD ),
			'ID' => __( 'Indonesia', self::TD ),
			'IR' => __( 'Iran', self::TD ),
			'IQ' => __( 'Iraq', self::TD ),
			'IE' => __( 'Ireland', self::TD ),
			'IM' => __( 'Isle of Man', self::TD ),
			'IL' => __( 'Israel', self::TD ),
			'IT' => __( 'Italy', self::TD ),
			'JM' => __( 'Jamaica', self::TD ),
			'JP' => __( 'Japan', self::TD ),
			'JE' => __( 'Jersey', self::TD ),
			'JO' => __( 'Jordan', self::TD ),
			'KZ' => __( 'Kazakhstan', self::TD ),
			'KE' => __( 'Kenya', self::TD ),
			'KI' => __( 'Kiribati', self::TD ),
			'KW' => __( 'Kuwait', self::TD ),
			'KG' => __( 'Kyrgyzstan', self::TD ),
			'LA' => __( 'Laos', self::TD ),
			'LV' => __( 'Latvia', self::TD ),
			'LB' => __( 'Lebanon', self::TD ),
			'LS' => __( 'Lesotho', self::TD ),
			'LR' => __( 'Liberia', self::TD ),
			'LY' => __( 'Libya', self::TD ),
			'LI' => __( 'Liechtenstein', self::TD ),
			'LT' => __( 'Lithuania', self::TD ),
			'LU' => __( 'Luxembourg', self::TD ),
			'MO' => __( 'Macao', self::TD ),
			'MG' => __( 'Madagascar', self::TD ),
			'MW' => __( 'Malawi', self::TD ),
			'MY' => __( 'Malaysia', self::TD ),
			'MV' => __( 'Maldives', self::TD ),
			'ML' => __( 'Mali', self::TD ),
			'MT' => __( 'Malta', self::TD ),
			'MH' => __( 'Marshall Islands', self::TD ),
			'MQ' => __( 'Martinique', self::TD ),
			'MR' => __( 'Mauritania', self::TD ),
			'MU' => __( 'Mauritius', self::TD ),
			'YT' => __( 'Mayotte', self::TD ),
			'MX' => __( 'Mexico', self::TD ),
			'FM' => __( 'Micronesia', self::TD ),
			'MD' => __( 'Moldova', self::TD ),
			'MC' => __( 'Monaco', self::TD ),
			'MN' => __( 'Mongolia', self::TD ),
			'ME' => __( 'Montenegro', self::TD ),
			'MS' => __( 'Montserrat', self::TD ),
			'MA' => __( 'Morocco', self::TD ),
			'MZ' => __( 'Mozambique', self::TD ),
			'MM' => __( 'Myanmar', self::TD ),
			'NA' => __( 'Namibia', self::TD ),
			'NR' => __( 'Nauru', self::TD ),
			'NP' => __( 'Nepal', self::TD ),
			'NL' => __( 'Netherlands', self::TD ),
			'NC' => __( 'New Caledonia', self::TD ),
			'NZ' => __( 'New Zealand', self::TD ),
			'NI' => __( 'Nicaragua', self::TD ),
			'NE' => __( 'Niger', self::TD ),
			'NG' => __( 'Nigeria', self::TD ),
			'NU' => __( 'Niue', self::TD ),
			'NF' => __( 'Norfolk Island', self::TD ),
			'KP' => __( 'North Korea', self::TD ),
			'MK' => __( 'North Macedonia', self::TD ),
			'MP' => __( 'Northern Mariana Islands', self::TD ),
			'NO' => __( 'Norway', self::TD ),
			'OM' => __( 'Oman', self::TD ),
			'PK' => __( 'Pakistan', self::TD ),
			'PW' => __( 'Palau', self::TD ),
			'PS' => __( 'Palestine', self::TD ),
			'PA' => __( 'Panama', self::TD ),
			'PG' => __( 'Papua New Guinea', self::TD ),
			'PY' => __( 'Paraguay', self::TD ),
			'PE' => __( 'Peru', self::TD ),
			'PH' => __( 'Philippines', self::TD ),
			'PN' => __( 'Pitcairn Islands', self::TD ),
			'PL' => __( 'Poland', self::TD ),
			'PT' => __( 'Portugal', self::TD ),
			'PR' => __( 'Puerto Rico', self::TD ),
			'QA' => __( 'Qatar', self::TD ),
			'RE' => __( 'Réunion', self::TD ),
			'RO' => __( 'Romania', self::TD ),
			'RU' => __( 'Russia', self::TD ),
			'RW' => __( 'Rwanda', self::TD ),
			'BL' => __( 'Saint Barthélemy', self::TD ),
			'SH' => __( 'Saint Helena', self::TD ),
			'KN' => __( 'Saint Kitts and Nevis', self::TD ),
			'LC' => __( 'Saint Lucia', self::TD ),
			'MF' => __( 'Saint Martin', self::TD ),
			'PM' => __( 'Saint Pierre and Miquelon', self::TD ),
			'VC' => __( 'Saint Vincent and the Grenadines', self::TD ),
			'WS' => __( 'Samoa', self::TD ),
			'SM' => __( 'San Marino', self::TD ),
			'ST' => __( 'São Tomé and Príncipe', self::TD ),
			'SA' => __( 'Saudi Arabia', self::TD ),
			'SN' => __( 'Senegal', self::TD ),
			'RS' => __( 'Serbia', self::TD ),
			'SC' => __( 'Seychelles', self::TD ),
			'SL' => __( 'Sierra Leone', self::TD ),
			'SG' => __( 'Singapore', self::TD ),
			'SX' => __( 'Sint Maarten', self::TD ),
			'SK' => __( 'Slovakia', self::TD ),
			'SI' => __( 'Slovenia', self::TD ),
			'SB' => __( 'Solomon Islands', self::TD ),
			'SO' => __( 'Somalia', self::TD ),
			'ZA' => __( 'South Africa', self::TD ),
			'KR' => __( 'South Korea', self::TD ),
			'SS' => __( 'South Sudan', self::TD ),
			'ES' => __( 'Spain', self::TD ),
			'LK' => __( 'Sri Lanka', self::TD ),
			'SD' => __( 'Sudan', self::TD ),
			'SR' => __( 'Suriname', self::TD ),
			'SE' => __( 'Sweden', self::TD ),
			'CH' => __( 'Switzerland', self::TD ),
			'SY' => __( 'Syria', self::TD ),
			'TW' => __( 'Taiwan', self::TD ),
			'TJ' => __( 'Tajikistan', self::TD ),
			'TZ' => __( 'Tanzania', self::TD ),
			'TH' => __( 'Thailand', self::TD ),
			'TL' => __( 'Timor-Leste', self::TD ),
			'TG' => __( 'Togo', self::TD ),
			'TK' => __( 'Tokelau', self::TD ),
			'TO' => __( 'Tonga', self::TD ),
			'TT' => __( 'Trinidad and Tobago', self::TD ),
			'TN' => __( 'Tunisia', self::TD ),
			'TR' => __( 'Türkiye', self::TD ),
			'TM' => __( 'Turkmenistan', self::TD ),
			'TC' => __( 'Turks and Caicos Islands', self::TD ),
			'TV' => __( 'Tuvalu', self::TD ),
			'UG' => __( 'Uganda', self::TD ),
			'UA' => __( 'Ukraine', self::TD ),
			'AE' => __( 'United Arab Emirates', self::TD ),
			'GB' => __( 'United Kingdom', self::TD ),
			'US' => __( 'United States', self::TD ),
			'VI' => __( 'U.S. Virgin Islands', self::TD ),
			'UY' => __( 'Uruguay', self::TD ),
			'UZ' => __( 'Uzbekistan', self::TD ),
			'VU' => __( 'Vanuatu', self::TD ),
			'VA' => __( 'Vatican City', self::TD ),
			'VE' => __( 'Venezuela', self::TD ),
			'VN' => __( 'Vietnam', self::TD ),
			'WF' => __( 'Wallis and Futuna', self::TD ),
			'EH' => __( 'Western Sahara', self::TD ),
			'YE' => __( 'Yemen', self::TD ),
			'ZM' => __( 'Zambia', self::TD ),
			'ZW' => __( 'Zimbabwe', self::TD ),
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

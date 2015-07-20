<?php
namespace MoEngine;
/**
 * Description of Timezone
 *
 * @author Maurice Prosper <maurice@ParkShade.com>
 * @package ParkShade
 */
class Timezone extends \DateTimeZone implements Loggable {
	/**
	 * Full list of common timezone ID's and their common name
	 * @var array
	 */
	public static $timezones = array(
		'Asia/Muscat'	=> 'Abu Dhabi, Muscat',
		'Australia/Adelaide'	=> 'Adelaide',
		'America/Anchorage'	=> 'Alaska',
		'Asia/Almaty'	=> 'Almaty',
		'Europe/Amsterdam'	=> 'Amsterdam',
		'Asia/Dhaka'	=> 'Astana, Dhaka',
		'Europe/Athens'	=> 'Athens',
		'Canada/Atlantic'	=> 'Atlantic Time (Canada)',
		'Pacific/Auckland'	=> 'Auckland, Wellington',
		'Atlantic/Azores'	=> 'Azores',
		'Asia/Baghdad'	=> 'Baghdad',
		'Asia/Baku'	=> 'Baku',
		'Asia/Bangkok'	=> 'Bangkok, Hanoi',
		'Europe/Belgrade'	=> 'Belgrade',
		'Europe/Berlin'	=> 'Berlin, Bern',
		'America/Bogota'	=> 'Bogota, Quito',
		'America/Sao_Paulo'	=> 'Brasilia',
		'Europe/Bratislava'	=> 'Bratislava',
		'Australia/Brisbane'	=> 'Brisbane',
		'Europe/Brussels'	=> 'Brussels',
		'Europe/Bucharest'	=> 'Bucharest',
		'Europe/Budapest'	=> 'Budapest',
		'America/Argentina/Buenos_Aires'	=> 'Buenos Aires, Georgetown',
		'Africa/Cairo'	=> 'Cairo',
		'Australia/Canberra'	=> 'Canberra',
		'Atlantic/Cape_Verde'	=> 'Cape Verde Is.',
		'America/Caracas'	=> 'Caracas',
		'Africa/Casablanca'	=> 'Casablanca',
		'America/Managua'	=> 'Central America',
		'America/Chicago'	=> 'Central Time (US &amp; Canada)',
		'America/Chihuahua'	=> 'Chihuahua, La Paz',
		'Asia/Chongqing'	=> 'Chongqing',
		'Europe/Copenhagen'	=> 'Copenhagen',
		'Australia/Darwin'	=> 'Darwin',
		'Europe/Dublin'	=> 'Dublin',
		'America/New_York'	=> 'Eastern Time (US &amp; Canada)',
		'Asia/Yekaterinburg'	=> 'Ekaterinburg',
		'Pacific/Fiji'	=> 'Fiji, Marshall Is.',
		'America/Godthab'	=> 'Greenland',
		'Pacific/Guam'	=> 'Guam',
		'Asia/Bangkok'	=> 'Hanoi',
		'Africa/Harare'	=> 'Harare',
		'Pacific/Honolulu'	=> 'Hawaii',
		'Europe/Helsinki'	=> 'Helsinki, Kyiv',
		'Australia/Hobart'	=> 'Hobart',
		'Asia/Hong_Kong'	=> 'Hong Kong, Beijing',
		'Pacific/Kwajalein'	=> 'International Date Line West',
		'Asia/Irkutsk'	=> 'Irkutsk',
		'Europe/Istanbul'	=> 'Istanbul',
		'Asia/Jakarta'	=> 'Jakarta',
		'Asia/Jerusalem'	=> 'Jerusalem',
		'Asia/Kabul'	=> 'Kabul',
		'Asia/Kamchatka'	=> 'Kamchatka',
		'Asia/Karachi'	=> 'Karachi, Islamabad',
		'Asia/Katmandu'	=> 'Kathmandu',
		'Asia/Kolkata'	=> 'Kolkata',
		'Asia/Krasnoyarsk'	=> 'Krasnoyarsk',
		'Asia/Kuala_Lumpur'	=> 'Kuala Lumpur',
		'Asia/Kuwait'	=> 'Kuwait',
		'America/La_Paz'	=> 'La Paz',
		'America/Lima'	=> 'Lima',
		'Europe/Lisbon'	=> 'Lisbon',
		'Europe/Ljubljana'	=> 'Ljubljana',
		'Europe/London'	=> 'London, Edinburgh',
		'Europe/Madrid'	=> 'Madrid',
		'Asia/Magadan'	=> 'Magadan, New Caledonia, Solomon Is.',
		'America/Mazatlan'	=> 'Mazatlan',
		'Australia/Melbourne'	=> 'Melbourne',
		'America/Mexico_City'	=> 'Mexico City, Guadalajara',
		'America/Noronha'	=> 'Mid-Atlantic',
		'Pacific/Midway'	=> 'Midway Island',
		'Europe/Minsk'	=> 'Minsk',
		'Africa/Monrovia'	=> 'Monrovia',
		'America/Monterrey'	=> 'Monterrey',
		'Europe/Moscow'	=> 'Moscow, St. Petersburg',
		'America/Denver'	=> 'Mountain Time (US &amp; Canada)',
		'Africa/Nairobi'	=> 'Nairobi',
		'Asia/Calcutta'	=> 'New Delhi, Chennai, Mumbai, Sri Jayawardenepura',
		'America/St_Johns'	=> 'Newfoundland',
		'Asia/Novosibirsk'	=> 'Novosibirsk',
		'Pacific/Tongatapu'	=> 'Nuku\'alofa',
		'America/Los_Angeles'	=> 'Pacific Time (US &amp; Canada)',
		'Europe/Paris'	=> 'Paris',
		'Australia/Perth'	=> 'Perth',
		'Pacific/Port_Moresby'	=> 'Port Moresby',
		'Europe/Prague'	=> 'Prague',
		'Africa/Johannesburg'	=> 'Pretoria',
		'Asia/Rangoon'	=> 'Rangoon',
		'Europe/Riga'	=> 'Riga',
		'Asia/Riyadh'	=> 'Riyadh',
		'Europe/Rome'	=> 'Rome',
		'Pacific/Samoa'	=> 'Samoa',
		'America/Santiago'	=> 'Santiago',
		'Europe/Sarajevo'	=> 'Sarajevo',
		'Asia/Seoul'	=> 'Seoul',
		'Asia/Singapore'	=> 'Singapore',
		'Europe/Skopje'	=> 'Skopje',
		'Europe/Sofia'	=> 'Sofia',
		'Europe/Stockholm'	=> 'Stockholm',
		'Australia/Sydney'	=> 'Sydney',
		'Asia/Taipei'	=> 'Taipei',
		'Europe/Tallinn'	=> 'Tallinn',
		'Asia/Tashkent'	=> 'Tashkent',
		'Asia/Tbilisi'	=> 'Tbilisi',
		'Asia/Tehran'	=> 'Tehran',
		'America/Tijuana'	=> 'Tijuana',
		'Asia/Tokyo'	=> 'Tokyo, Osaka, Sapporo',
		'Asia/Ulan_Bator'	=> 'Ulaan Bataar',
		'Asia/Urumqi'	=> 'Urumqi',
		'Europe/Vienna'	=> 'Vienna',
		'Europe/Vilnius'	=> 'Vilnius',
		'Asia/Vladivostok'	=> 'Vladivostok',
		'Europe/Volgograd'	=> 'Volgograd',
		'Europe/Warsaw'	=> 'Warsaw',
		'Africa/Lagos'	=> 'West Central Africa',
		'Asia/Yakutsk'	=> 'Yakutsk',
		'Asia/Yerevan'	=> 'Yerevan',
		'Europe/Zagreb'	=> 'Zagreb',
	);

	/**
	 * Ruby Timezone ID's to PHP's timezone ID's
	 * @var array
	 */
	private static $mapRuby = array(
		'International Date Line West' => 'Pacific/Midway',
		'Midway Island' => 'Pacific/Midway',
		'American Samoa' => 'Pacific/Pago_Pago',
		'Hawaii' => 'Pacific/Honolulu',
		'Alaska' => 'America/Juneau',
		'Pacific Time (US & Canada)' => 'America/Los_Angeles',
		'Tijuana' => 'America/Tijuana',
		'Mountain Time (US & Canada)' => 'America/Denver',
		'Arizona' => 'America/Phoenix',
		'Chihuahua' => 'America/Chihuahua',
		'Mazatlan' => 'America/Mazatlan',
		'Central Time (US & Canada)' => 'America/Chicago',
		'Saskatchewan' => 'America/Regina',
		'Guadalajara' => 'America/Mexico_City',
		'Mexico City' => 'America/Mexico_City',
		'Monterrey' => 'America/Monterrey',
		'Central America' => 'America/Guatemala',
		'Eastern Time (US & Canada)' => 'America/New_York',
		'Indiana (East)' => 'America/Indiana/Indianapolis',
		'Bogota' => 'America/Bogota',
		'Lima' => 'America/Lima',
		'Quito' => 'America/Lima',
		'Atlantic Time (Canada)' => 'America/Halifax',
		'Caracas' => 'America/Caracas',
		'La Paz' => 'America/La_Paz',
		'Santiago' => 'America/Santiago',
		'Newfoundland' => 'America/St_Johns',
		'Brasilia' => 'America/Sao_Paulo',
		'Buenos Aires' => 'America/Argentina/Buenos_Aires',
		'Montevideo' => 'America/Montevideo',
		'Georgetown' => 'America/Guyana',
		'Greenland' => 'America/Godthab',
		'Mid-Atlantic' => 'Atlantic/South_Georgia',
		'Azores' => 'Atlantic/Azores',
		'Cape Verde Is.' => 'Atlantic/Cape_Verde',
		'Dublin' => 'Europe/Dublin',
		'Edinburgh' => 'Europe/London',
		'Lisbon' => 'Europe/Lisbon',
		'London' => 'Europe/London',
		'Casablanca' => 'Africa/Casablanca',
		'Monrovia' => 'Africa/Monrovia',
		'UTC' => 'Etc/UTC',
		'Belgrade' => 'Europe/Belgrade',
		'Bratislava' => 'Europe/Bratislava',
		'Budapest' => 'Europe/Budapest',
		'Ljubljana' => 'Europe/Ljubljana',
		'Prague' => 'Europe/Prague',
		'Sarajevo' => 'Europe/Sarajevo',
		'Skopje' => 'Europe/Skopje',
		'Warsaw' => 'Europe/Warsaw',
		'Zagreb' => 'Europe/Zagreb',
		'Brussels' => 'Europe/Brussels',
		'Copenhagen' => 'Europe/Copenhagen',
		'Madrid' => 'Europe/Madrid',
		'Paris' => 'Europe/Paris',
		'Amsterdam' => 'Europe/Amsterdam',
		'Berlin' => 'Europe/Berlin',
		'Bern' => 'Europe/Berlin',
		'Rome' => 'Europe/Rome',
		'Stockholm' => 'Europe/Stockholm',
		'Vienna' => 'Europe/Vienna',
		'West Central Africa' => 'Africa/Algiers',
		'Bucharest' => 'Europe/Bucharest',
		'Cairo' => 'Africa/Cairo',
		'Helsinki' => 'Europe/Helsinki',
		'Kyiv' => 'Europe/Kiev',
		'Riga' => 'Europe/Riga',
		'Sofia' => 'Europe/Sofia',
		'Tallinn' => 'Europe/Tallinn',
		'Vilnius' => 'Europe/Vilnius',
		'Athens' => 'Europe/Athens',
		'Istanbul' => 'Europe/Istanbul',
		'Minsk' => 'Europe/Minsk',
		'Jerusalem' => 'Asia/Jerusalem',
		'Harare' => 'Africa/Harare',
		'Pretoria' => 'Africa/Johannesburg',
		'Moscow' => 'Europe/Moscow',
		'St. Petersburg' => 'Europe/Moscow',
		'Volgograd' => 'Europe/Moscow',
		'Kuwait' => 'Asia/Kuwait',
		'Riyadh' => 'Asia/Riyadh',
		'Nairobi' => 'Africa/Nairobi',
		'Baghdad' => 'Asia/Baghdad',
		'Tehran' => 'Asia/Tehran',
		'Abu Dhabi' => 'Asia/Muscat',
		'Muscat' => 'Asia/Muscat',
		'Baku' => 'Asia/Baku',
		'Tbilisi' => 'Asia/Tbilisi',
		'Yerevan' => 'Asia/Yerevan',
		'Kabul' => 'Asia/Kabul',
		'Ekaterinburg' => 'Asia/Yekaterinburg',
		'Islamabad' => 'Asia/Karachi',
		'Karachi' => 'Asia/Karachi',
		'Tashkent' => 'Asia/Tashkent',
		'Chennai' => 'Asia/Kolkata',
		'Kolkata' => 'Asia/Kolkata',
		'Mumbai' => 'Asia/Kolkata',
		'New Delhi' => 'Asia/Kolkata',
		'Kathmandu' => 'Asia/Kathmandu',
		'Astana' => 'Asia/Dhaka',
		'Dhaka' => 'Asia/Dhaka',
		'Sri Jayawardenepura' => 'Asia/Colombo',
		'Almaty' => 'Asia/Almaty',
		'Novosibirsk' => 'Asia/Novosibirsk',
		'Rangoon' => 'Asia/Rangoon',
		'Bangkok' => 'Asia/Bangkok',
		'Hanoi' => 'Asia/Bangkok',
		'Jakarta' => 'Asia/Jakarta',
		'Krasnoyarsk' => 'Asia/Krasnoyarsk',
		'Beijing' => 'Asia/Shanghai',
		'Chongqing' => 'Asia/Chongqing',
		'Hong Kong' => 'Asia/Hong_Kong',
		'Urumqi' => 'Asia/Urumqi',
		'Kuala Lumpur' => 'Asia/Kuala_Lumpur',
		'Singapore' => 'Asia/Singapore',
		'Taipei' => 'Asia/Taipei',
		'Perth' => 'Australia/Perth',
		'Irkutsk' => 'Asia/Irkutsk',
		'Ulaanbaatar' => 'Asia/Ulaanbaatar',
		'Seoul' => 'Asia/Seoul',
		'Osaka' => 'Asia/Tokyo',
		'Sapporo' => 'Asia/Tokyo',
		'Tokyo' => 'Asia/Tokyo',
		'Yakutsk' => 'Asia/Yakutsk',
		'Darwin' => 'Australia/Darwin',
		'Adelaide' => 'Australia/Adelaide',
		'Canberra' => 'Australia/Melbourne',
		'Melbourne' => 'Australia/Melbourne',
		'Sydney' => 'Australia/Sydney',
		'Brisbane' => 'Australia/Brisbane',
		'Hobart' => 'Australia/Hobart',
		'Vladivostok' => 'Asia/Vladivostok',
		'Guam' => 'Pacific/Guam',
		'Port Moresby' => 'Pacific/Port_Moresby',
		'Magadan' => 'Asia/Magadan',
		'Solomon Is.' => 'Pacific/Guadalcanal',
		'New Caledonia' => 'Pacific/Noumea',
		'Fiji' => 'Pacific/Fiji',
		'Kamchatka' => 'Asia/Kamchatka',
		'Marshall Is.' => 'Pacific/Majuro',
		'Auckland' => 'Pacific/Auckland',
		'Wellington' => 'Pacific/Auckland',
		'Nuku\'alofa' => 'Pacific/Tongatapu',
		'Tokelau Is.' => 'Pacific/Fakaofo',
		'Chatham Is.' => 'Pacific/Chatham',
		'Samoa' => 'Pacific/Apia',
	);

	/**
	 * Creates a Timezone, but it can be generated using only an offset
	 *
	 * @param string|int $timezone The name of the time zone or its UTC offset
	 * @param boolean $isDst Is the offset for the timezone when it's in daylight savings time?
	 */
    public function __construct($timezone, $isDst = null) {
		// convert name from Ruby times
		if(isset(static::$mapRuby[ $timezone ]))
			$timezone = static::$mapRuby[ $timezone ];

		elseif(is_numeric($timezone)) {
			$zone = timezone_name_from_abbr(null, $timezone, $isDst);

			if($zone === false)
				foreach (timezone_abbreviations_list() as $abbr) {
					foreach ($abbr as $city)
						if(!empty($city['timezone_id']) && $city['offset'] == $timezone && (!isset($isDst) || $city['dst'] === $isDst)) {
							$zone = $city['timezone_id'];
							break;
						}
				}

			if($zone)
				$timezone = $zone;
			// else: timezone cant be made for this offset
		}

		parent::__construct($timezone);
	}

	/**
	 * Save name of timezone
	 * @return array
	 */
	public function __sleep() {
		$this->name = $this->getName();
		return array('name');
	}

	/**
	 * Set timezone name
	 */
	public function __wakeup() {
		$this->__construct( $this->name );
		unset($this->name);
	}

	public function escape() {
		return $this->getName();
	}
}
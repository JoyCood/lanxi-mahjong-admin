<?php 
//require_once('lib/maxmindDB/vendor/autoload.php');
use MaxMind\Db\Reader;

class Maxmind {
    const DBFile = 'lib/maxmindDB/GeoLite2-City.mmdb';
    private static $maxmindDB;

    static public function getCountryCode($ip) {
        if(!is_object(self::$maxmindDB)) {
            self::$maxmindDB = new Reader(self::DBFile);
        } 
        $code = array(
            'country_code' => '',
            'city_code' => '',
        );

        try {
            $result = self::$maxmindDB->get($ip);
            if(!empty($result['country']['iso_code'])) {
               $code['country_code'] = $result['country']['iso_code']; 
            }
            if(!empty($result['city']['geoname_id'])) {
                $code['city_code'] = $result['city']['geoname_id'];
            }
        } catch ( Exception $e) {}

        return $code;
    } 
}

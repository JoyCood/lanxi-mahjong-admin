<?php !defined('TANG_FENG') AND exit('Access Denied!');
    
class Helper
{
    const ENCRYPT_METHOD = 'aes-256-cbc';
    const ENCRYPT_KEY    = '^lanxi@YiyuTech_168^';
    const ENCRYPT_IV     = '@lanxi@YiyuTech_888@';

	/**
	 * 返回大于或等于0的整数
	 *
	 * @author joy.cood
	 * 
	 * @param $num  mix
	 *
	 * @return int
	 */
	public static function uint($num)
	{
		return max(0, (int)$num);
	}

    public static function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
     * 返回数组差集
     *
     * @author joy.cood
     *
     * @param $data   array
     * @param $filter array
     *
     * @return array|null
     */
	public static function filter($data, $filter)
	{
        return array_diff_key($data, array_flip($filter));
	}

     /**
     * 返回数组交集
     *
     * @author joy.cood
     *
     * @param $data    array
     * @param $allowed array
     *
     * @return array|null
     */
    public static function allowed($data, $allowed)
    {
    	return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * 加密
     *
     * @param $data string
     *
     * @return string
     */
    public static function encrypt($data) {
        $ivLenth = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
        $iv = substr(self::ENCRYPT_IV, 0, $ivLenth);
        return openssl_encrypt(base64_encode($data), self::ENCRYPT_METHOD, self::ENCRYPT_KEY, 0, $iv); 
    }

    /**
     * 解密
     *
     * @param $data string
     *
     * @return string
     */
    public static function decrypt($data) {
        $ivLenth = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
        $iv = substr(self::ENCRYPT_IV, 0, $ivLenth);
        $data = openssl_decrypt($data, self::ENCRYPT_METHOD, self::ENCRYPT_KEY, 0, $iv);
       return base64_decode($data); 
    }
    
    /**
     * 加密参数
     *
     * @param $params array
     *
     * @return string
     */
    public static function encodeParams($params) {
        ksort($params);
        $params = http_build_query($params);
        return self::encrypt($params);
    }

    /**
     * 将加密后的参数解密
     *
     * @param $data string
     *
     * @return array
     */
    public static function decodeParams($params) {
        $params = self::decrypt($params);
        if(!$params) {
            return false;
        }

        $params = self::parseQueryString($params);
        ksort($params);
        return $params;
    }

    /**
     * 将url中的参数转换成数组
     *
     * @author afoskoo
     *
     * @param $url    string
     *
     * @return array
     */
    public static function parseQueryString($url) {
        $tmp    = explode('?', $url);
        $query  = isset($tmp[1])? $tmp[1]: $tmp[0];
        $rs     = array();

        parse_str($query, $rs);
        return $rs;
    }

    static public function isVideo($src) {
        return (strpos($src, '/video/') !== false) || (strpos($src, '/video-') !== false);
    }

    static public function imageSrc($src, $prefix) {
        $tmp    = explode('/', $src);
        $sizeof = sizeof($tmp);
        if($sizeof > 1) {
            $filename = $tmp[$sizeof - 1];
            $tmp[$sizeof - 1] = $prefix. '-'. $tmp[$sizeof - 1];
            $src = join('/', $tmp);
        }
        return $src;
    }

    static public function getValue($ary, $key, $defaultValue = null) {
        return isset($ary[$key])? 
            $ary[$key]:
            $defaultValue;
    }

    static public function popValue(&$ary, $key, $defaultValue = null) {
        $val = self::getValue($ary, $key, $defaultValue);
        unset($ary[$key]);
        return $val;
    }

    static public function mkrand()
    {
        mt_srand(self::make_seed());
        return mt_rand();
    }

    static public function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }

	static public function filesize($url) 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		curl_close($ch);
		return $size;
    }
}

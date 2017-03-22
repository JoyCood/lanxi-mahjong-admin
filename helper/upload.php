<?php !defined('YOUNG_TEAM') AND exit('Access Denied!');

/**
 * 上传文件
 */
class Upload {
	/**
	 * 上传文件
	 *
	 * @author joy.cood
	 *
	 * @param $filePath	string     要上传文件 
	 * @param $serverPath sting    要保存到服务器的路径
	 *
	 * @return true|false
	 */
	public static function put($filePath, $serverPath, $fileType, $format = 'default') {
		require_once(LIB_DIR. 'youpaiyun/upyun.class.php');
        $result	= array();

        // 获取空间帐号信息
        if($fileType == 'image') {
	        $host         = Config::$storage['image']['host'];
	        $bucketName   = Config::$storage['image']['bucket'];
			$operatorName = Config::$storage['image']['user'];
			$operatorPwd  = Config::$storage['image']['pass'];
		} else if($fileType == 'file') {
	        $host         = Config::$storage['file']['host'];
	        $bucketName   = Config::$storage['file']['bucket'];
			$operatorName = Config::$storage['file']['user'];
			$operatorPwd  = Config::$storage['file']['pass'];
		} else {
	        $host         = Config::$storage['media']['host'];
	        $bucketName   = Config::$storage['media']['bucket'];
			$operatorName = Config::$storage['media']['user'];
			$operatorPwd  = Config::$storage['media']['pass'];
		}

		$upyun 	= new UpYun($bucketName, $operatorName, $operatorPwd);
		$fh 	= fopen($filePath, 'rb');

		// 获取resize设置
		$size 	= isset(Config::$imageSize[$format])?
					Config::$imageSize[$format]:
					null;
		$size 	= !is_array($size)? array('default' => $size): $size;
		// 按设置生成缩略图
		foreach($size as $key => $val) {
			$file = '/'. $serverPath;
			$opts = array();
			// 如果不是 default，生成新的文件名
			if($key != 'default') {
				$tmp 	= explode('/', $file);
				$sizeof = sizeof($tmp);
				if($sizeof > 1) {
					$tmp[$sizeof - 1] = $key. '-' . $tmp[$sizeof - 1];
					$file = join('/', $tmp);
				} else {
					$file = $file. '-'. $key;
				}
			}
			// 缩略图的设置项
			if(!is_null($val)) {
				$opts = array(
					UpYun::X_GMKERL_TYPE    => 'fix_width_or_height', 	// 缩略图类型
					UpYun::X_GMKERL_VALUE   => $val						// 缩略图大小
					// UpYun::X_GMKERL_QUALITY => 95, 					// 缩略图压缩质量
					// UpYun::X_GMKERL_UNSHARP => True 					// 是否进行锐化处理
				);
			}
			// 上传
			$rsp = $upyun->writeFile($file, $fh, true, $opts);

			$result[] = $host. $file;
		}
		return $result? $result[0]: false;
	}
}
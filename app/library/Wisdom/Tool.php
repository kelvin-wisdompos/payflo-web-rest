<?php

namespace Wisdom;

class Tool
{
    protected $config;

    public function __construct(\Phalcon\Config $Config)
    {
        $this->config = $Config;
    }

    public function adjustDate(string $timezone1, string $timezone2, string $date, string $format = 'Y-m-d H:i:s')
    {
        /**
         * convert date between timezone
         *
         * @return string
         */
        $myTimezone1 = new \DateTimeZone($timezone1);
        $myDate      = new \DateTime($date, $myTimezone1);
        if ($timezone1 === $timezone2) {
            return $myDate->format($format);
        }
        $myTimezone2 = new \DateTimeZone($timezone2);
        $myDate->setTimezone($myTimezone2);
        return $myDate->format($format);
    }

    public function adjustDbDate(string $date, string $timezone = null)
    {
        /**
         * convert date from db to app
         *
         * @return string
         */
        return $this->adjustDate($timezone ?? $this->config->database->timezone, $this->config->timezone, $date);
    }

    public function adjustAppDate(string $date, string $timezone = null)
    {
        /**
         * convert date from app to db
         *
         * @return string
         */
        return $this->adjustDate($this->config->timezone, $timezone ?? $this->config->database->timezone, $date);
    }

    public function getCurrentDate(string $timezone, string $format = 'Y-m-d H:i:s')
    {
        /**
         * get current date with timezone
         *
         * @return string
         */
        $myTimezone = new \DateTimeZone($timezone);
        $myDate     = new \DateTime('now', $myTimezone);
        return $myDate->format($format);
    }

    public function decodeBase64(string $string)
    {
        /**
         * decode base64
         *
         * @return mixed
         */
        $data   = explode(',', $string);
        if (count($data) != 2) {
            return false;
        }
        $type   = explode(';', $data[0]);
        if (count($type) != 2) {
            return false;
        }
        $type   = explode('/', $type[0]);
        if (count($type) != 2) {
            return false;
        }
        $result = base64_decode($data[1]);
        if (base64_encode($result) == $data[1]) {
            return [$result, $type[1]];
        } else {
            return false;
        }
    }

    public function base64StringToMonochrome(string $string)
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $string);
        $data = base64_decode($data);
        $im = imagecreatefromstring($data);
        $w = imagesx($im);
        $h = imagesy($im);
        $threshold = 150;

        imagefilter($im,IMG_FILTER_GRAYSCALE);
        $out = imagecreate($w,$h);
        $white = imagecolorallocate($out,255,255,255);
        $black = imagecolorallocate($out,0,0,0);

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $index  = imagecolorat($im, $x, $y);
                $grey   = imagecolorsforindex($im, $index)['red'];
                if ($grey <= $threshold) {
                    imagesetpixel($out,$x,$y,$black);
                }
            }
        }
        ob_start();
        imagepng($out);
        $stringdata = ob_get_contents();
        ob_end_clean(); 
        $base64monochrome = 'data:image/png;base64,' . base64_encode($stringdata);
        return $base64monochrome;
    }

    public function base64StringToFile(string $base64String, string $writeFolder, string $outputFile, array $allowed = [])
    {
        /**
         * convert base64 to file
         *
         * @return string
         */
        $decode      = $this->decodeBase64($base64String);
        if (!$decode) {
            throw new \Exception('Invalid base64', 400);
        }
        if (!empty($allowed) && !in_array($decode[1], $allowed)) {
            throw new \Exception('Invalid file', 400);
        }
        $outputFile .= '.' . $decode[1];
        //check folder exists
        if (!file_exists($writeFolder)) {
            mkdir($writeFolder, 755, true);
        }
        //open  file
        $file        = fopen($writeFolder . '/' . $outputFile, 'wb');
        //problem with open a file
        if (!$file) {
            throw new \Exception('Cannot open the file', 500);
        }
        //write file from base64 string
        $writeStatus = fwrite($file, $decode[0]);
        //problem with write
        if (!$writeStatus) {
            throw new \Exception('File is not writable', 500);
        }
        //close the file
        fclose($file);
        // change permissions
        chmod($writeFolder . '/' . $outputFile, 0644);
        //return file name
        return $writeFolder . '/' . $outputFile;
    }

    public function base64StringToBlob(string $base64String, array $allowed = [])
    {
        /**
         * convert base64 to file
         *
         * @return string
         */
        $decode = $this->decodeBase64($base64String);
        if (!$decode) {
            throw new \Exception('Invalid base64', 500);
        }
        if (!empty($allowed) && !in_array($decode[1], $allowed)) {
            throw new \Exception('Invalid file', 500);
        }
        return $decode[0];
    }

    public function convertBlobToImage(string $blob)
    {
        /**
         * convert blob to image base64 string
         *
         * @return mixed
         */
        if (empty($blob)) {
            return null;
        }
        $info = getimagesizefromstring($blob);
        if (!isset($info['mime'])) {
            return null;
        }
        return 'data:' . $info['mime'] . ';base64,' . base64_encode($blob);
    }

    /**
     * @source https://stackoverflow.com/questions/24233482/find-out-which-items-were-added-and-removed-in-an-array
     */
    public function array_diff_once($array1, $array2)
    {
        foreach ($array2 as $val) {
            if (false !== ($pos = array_search($val, $array1))) {
                unset($array1[$pos]);
            }
        }
        return $array1;
    }

    public function displayMoney($value, $currency)
    {
        $symbol = '$';
        if ($currency==='rupiah') {
            $symbol = 'Rp';
        }
        $display = $symbol;
        if ($currency==='rupiah') {
            $display .= number_format(floatval($value), 0, ',', '.');
        } else {
            $display .= number_format(floatval($value), 2, '.', ',');
        }
        return $display;
    }

    public static function predump($var, $exit = false)
    {
        echo('<pre>');
        var_dump($var);
        echo('</pre>');
        if ($exit) {
            exit;
        }
    }

    public static function curl(string $url, callable $onError = null) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_FOLLOWLOCATION => true, 
        ]);
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if ($error) {
            if ($onError) {
                $onError($error);
            }
            return;
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($onError) {
                $onError(json_last_error_msg());
            }
            return;
        }
    }
}

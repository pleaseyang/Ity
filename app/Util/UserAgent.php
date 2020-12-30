<?php

namespace App\Util;

class UserAgent
{
    private $platforms = [
        'windows nt 6.2' => 'Win8',
        'windows nt 6.1' => 'Win7',
        'windows nt 6.0' => 'Win Longhorn',
        'windows nt 5.2' => 'Win2003',
        'windows nt 5.0' => 'Win2000',
        'windows nt 5.1' => 'WinXP',
        'windows nt 4.0' => 'Windows NT 4.0',
        'winnt4.0' => 'Windows NT 4.0',
        'winnt 4.0' => 'Windows NT',
        'winnt' => 'Windows NT',
        'windows 98' => 'Win98',
        'win98' => 'Win98',
        'windows 95' => 'Win95',
        'win95' => 'Win95',
        'windows' => 'Unknown Windows OS',
        'os x' => 'MacOS X',
        'ppc mac' => 'Power PC Mac',
        'freebsd' => 'FreeBSD',
        'ppc' => 'Macintosh',
        'linux' => 'Linux',
        'debian' => 'Debian',
        'sunos' => 'Sun Solaris',
        'beos' => 'BeOS',
        'apachebench' => 'ApacheBench',
        'aix' => 'AIX',
        'irix' => 'Irix',
        'osf' => 'DEC OSF',
        'hp-ux' => 'HP-UX',
        'netbsd' => 'NetBSD',
        'bsdi' => 'BSDi',
        'openbsd' => 'OpenBSD',
        'gnu' => 'GNU/Linux',
        'unix' => 'Unknown Unix OS'
    ];

    private $browsers = [
        'Flock' => 'Flock',
        'Chrome' => 'Chrome',
        'Opera' => 'Opera',
        'MSIE' => 'IE',
        'Internet Explorer' => 'IE',
        'Shiira' => 'Shiira',
        'Firefox' => 'Firefox',
        'Chimera' => 'Chimera',
        'Phoenix' => 'Phoenix',
        'Firebird' => 'Firebird',
        'Camino' => 'Camino',
        'Netscape' => 'Netscape',
        'OmniWeb' => 'OmniWeb',
        'Safari' => 'Safari',
        'Mozilla' => 'Mozilla',
        'Konqueror' => 'Konqueror',
        'icab' => 'iCab',
        'Lynx' => 'Lynx',
        'Links' => 'Links',
        'hotjava' => 'HotJava',
        'amaya' => 'Amaya',
        'IBrowse' => 'IBrowse'
    ];

    private $mobiles = [
        // legacy array, old values commented out
        'mobileexplorer' => 'Mobile Explorer',
        'palmsource' => 'Palm',
        'palmscape' => 'Palmscape',

        // Phones and Manufacturers
        'motorola' => "Motorola",
        'nokia' => "Nokia",
        'palm' => "Palm",
        'iphone' => "Apple iPhone",
        'ipad' => "iPad",
        'ipod' => "Apple iPod Touch",
        'sony' => "Sony Ericsson",
        'ericsson' => "Sony Ericsson",
        'blackberry' => "BlackBerry",
        'cocoon' => "O2 Cocoon",
        'blazer' => "Treo",
        'lg' => "LG",
        'amoi' => "Amoi",
        'xda' => "XDA",
        'mda' => "MDA",
        'privateio' => "privateio",
        'htc' => "HTC",
        'samsung' => "Samsung",
        'sharp' => "Sharp",
        'sie-' => "Siemens",
        'alcatel' => "Alcatel",
        'benq' => "BenQ",
        'ipaq' => "HP iPaq",
        'mot-' => "Motorola",
        'playstation portable' => "PlayStation Portable",
        'hiptop' => "Danger Hiptop",
        'nec-' => "NEC",
        'panasonic' => "Panasonic",
        'philips' => "Philips",
        'sagem' => "Sagem",
        'sanyo' => "Sanyo",
        'spv' => "SPV",
        'zte' => "ZTE",
        'sendo' => "Sendo",

        // Operating Systems
        'symbian' => "Symbian",
        'SymbianOS' => "SymbianOS",
        'elaine' => "Palm",
        'series60' => "Symbian S60",
        'windows ce' => "Windows CE",

        // Browsers
        'obigo' => "Obigo",
        'netfront' => "Netfront Browser",
        'openwave' => "Openwave Browser",
        'mobilexplorer' => "Mobile Explorer",
        'operamini' => "Opera Mini",
        'opera mini' => "Opera Mini",

        // Other
        'digital paths' => "Digital Paths",
        'avantgo' => "AvantGo",
        'xiino' => "Xiino",
        'noprivatera' => "Noprivatera Transcoder",
        'vodafone' => "Vodafone",
        'docomo' => "NTT DoCoMo",
        'o2' => "O2",

        // Fallback
        'mobile' => "Generic Mobile",
        'wireless' => "Generic Mobile",
        'j2me' => "Generic Mobile",
        'midp' => "Generic Mobile",
        'cldc' => "Generic Mobile",
        'up.link' => "Generic Mobile",
        'up.browser' => "Generic Mobile",
        'smartphone' => "Generic Mobile",
        'cellphone' => "Generic Mobile"
    ];

    private $robots = [
        'googlebot' => 'Googlebot',
        'msnbot' => 'MSNBot',
        'slurp' => 'Inktomi Slurp',
        'yahoo' => 'Yahoo',
        'askjeeves' => 'AskJeeves',
        'fastcrawler' => 'FastCrawler',
        'infoseek' => 'InfoSeek Robot 1.0',
        'lycos' => 'Lycos'
    ];

    private $agent = null;

    private $is_browser = false;
    private $is_robot = false;
    private $is_mobile = false;

    private $languages = [];
    private $charsets = [];

    private $platform = '';
    private $browser = '';
    private $version = '';
    private $mobile = '';
    private $robot = '';

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @access    public
     * @param string $agent
     */
    public function __construct($agent = '')
    {
        if ($agent != '') {
            $this->agent = $agent;
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if (!is_null($this->agent)) {
            $this->compileData();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @access    private
     */
    private function compileData()
    {
        $this->setPlatform();

        foreach (array('setRobot', 'setBrowser', 'setMobile') as $function) {
            if ($this->$function() === true) {
                break;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the Platform
     *
     * @access    private
     */
    private function setPlatform()
    {
        if (is_array($this->platforms) and count($this->platforms) > 0) {
            foreach ($this->platforms as $key => $val) {
                if (preg_match("|" . preg_quote($key) . "|i", $this->agent)) {
                    $this->platform = $val;
                    return true;
                }
            }
        }
        $this->platform = 'Unknown Platform';
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Browser
     *
     * @access    private
     * @return    bool
     */
    private function setBrowser()
    {
        if (is_array($this->browsers) and count($this->browsers) > 0) {
            foreach ($this->browsers as $key => $val) {
                if (preg_match("|" . preg_quote($key) . ".*?([0-9\.]+)|i", $this->agent, $match)) {
                    $this->is_browser = true;
                    $this->version = $match[1];
                    $this->browser = $val;
                    $this->setMobile();
                    return true;
                }
            }
        }
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Robot
     *
     * @access    private
     * @return    bool
     */
    private function setRobot()
    {
        if (is_array($this->robots) and count($this->robots) > 0) {
            foreach ($this->robots as $key => $val) {
                if (preg_match("|" . preg_quote($key) . "|i", $this->agent)) {
                    $this->is_robot = true;
                    $this->robot = $val;
                    return true;
                }
            }
        }
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mobile Device
     *
     * @access    private
     * @return    bool
     */
    private function setMobile()
    {
        if (is_array($this->mobiles) and count($this->mobiles) > 0) {
            foreach ($this->mobiles as $key => $val) {
                if (false !== (strpos(strtolower($this->agent), $key))) {
                    $this->is_mobile = true;
                    $this->mobile = $val;
                    return true;
                }
            }
        }
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Set the accepted languages
     *
     * @access    private
     * @return    void
     */
    private function setLanguages()
    {
        if ((count($this->languages) == 0) and
            isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') {
            $languages = preg_replace('/(;q=[0-9.]+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));

            $this->languages = explode(',', $languages);
        }

        if (count($this->languages) == 0) {
            $this->languages = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the accepted character sets
     *
     * @access    private
     * @return    void
     */
    private function setCharsets()
    {
        if ((count($this->charsets) == 0) and
            isset($_SERVER['HTTP_ACCEPT_CHARSET']) and
            $_SERVER['HTTP_ACCEPT_CHARSET'] != '') {
            $charsets = preg_replace('/(;q=.+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])));

            $this->charsets = explode(',', $charsets);
        }

        if (count($this->charsets) == 0) {
            $this->charsets = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Is Browser
     *
     * @access    public
     * @param null $key
     * @return    bool
     */
    public function isBrowser($key = null)
    {
        if (!$this->is_browser) {
            return false;
        }

        // No need to be specific, it's a browser
        if ($key === null) {
            return true;
        }

        // Check for a specific browser
        return array_key_exists($key, $this->browsers) and $this->browser === $this->browsers[$key];
    }

    // --------------------------------------------------------------------

    /**
     * Is Robot
     *
     * @access    public
     * @param null $key
     * @return    bool
     */
    public function isRobot($key = null)
    {
        if (!$this->is_robot) {
            return false;
        }

        // No need to be specific, it's a robot
        if ($key === null) {
            return true;
        }

        // Check for a specific robot
        return array_key_exists($key, $this->robots) and $this->robot === $this->robots[$key];
    }

    // --------------------------------------------------------------------

    /**
     * Is Mobile
     *
     * @access    public
     * @param null $key
     * @return    bool
     */
    public function isMobile($key = null)
    {
        if (!$this->is_mobile) {
            return false;
        }

        // No need to be specific, it's a mobile
        if ($key === null) {
            return true;
        }

        // Check for a specific robot
        return array_key_exists($key, $this->mobiles) and $this->mobile === $this->mobiles[$key];
    }

    // --------------------------------------------------------------------

    /**
     * Is this a referral from another site?
     *
     * @access    public
     * @return    bool
     */
    public function isReferral()
    {
        if (!isset($_SERVER['HTTP_REFERER']) or $_SERVER['HTTP_REFERER'] == '') {
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Agent String
     *
     * @access    public
     * @return    string
     */
    public function agentString()
    {
        return $this->agent;
    }

    // --------------------------------------------------------------------

    /**
     * Get Platform
     *
     * @access    public
     * @return    string
     */
    public function platform()
    {
        return $this->platform;
    }

    // --------------------------------------------------------------------

    /**
     * Get Browser Name
     *
     * @access    public
     * @return    string
     */
    public function browser()
    {
        return $this->browser;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Browser Version
     *
     * @access    public
     * @return    string
     */
    public function version()
    {
        return $this->version;
    }

    // --------------------------------------------------------------------

    /**
     * Get The Robot Name
     *
     * @access    public
     * @return    string
     */
    public function robot()
    {
        return $this->robot;
    }
    // --------------------------------------------------------------------

    /**
     * Get the Mobile Device
     *
     * @access    public
     * @return    string
     */
    public function mobile()
    {
        return $this->mobile;
    }

    // --------------------------------------------------------------------

    /**
     * Get the referrer
     *
     * @access    public
     * @return    bool
     */
    public function referrer()
    {
        return (!isset($_SERVER['HTTP_REFERER']) or $_SERVER['HTTP_REFERER'] == '') ?
            '' :
            trim($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted languages
     *
     * @access    public
     * @return    array
     */
    public function languages()
    {
        if (count($this->languages) == 0) {
            $this->setLanguages();
        }

        return $this->languages;
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted Character Sets
     *
     * @access    public
     * @return    array
     */
    public function charsets()
    {
        if (count($this->charsets) == 0) {
            $this->setCharsets();
        }

        return $this->charsets;
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular language
     *
     * @access    public
     * @param string $lang
     * @return    bool
     */
    public function acceptLang($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->languages(), true));
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular character set
     *
     * @access    public
     * @param string $charset
     * @return    bool
     */
    public function acceptCharset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->charsets(), true));
    }
}

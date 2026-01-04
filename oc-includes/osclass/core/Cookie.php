<?php
if(!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

/*
 * Copyright 2014 Osclass
 * Copyright 2025 Osclass by OsclassPoint.com
 *
 * Osclass maintained & developed by OsclassPoint.com
 * You may not use this file except in compliance with the License.
 * You may download copy of Osclass at
 *
 *     https://osclass-classifieds.com/download
 *
 * Do not edit or add to this file if you wish to upgrade Osclass to newer
 * versions in the future. Software is distributed on an "AS IS" basis, without
 * warranties or conditions of any kind, either express or implied. Do not remove
 * this NOTICE section as it contains license information and copyrights.
 */


class Cookie {
  public $name;
  public $val;
  public $expires;
  
  private static $instance;

  /**
   * @return \Cookie
   */
  public static function newInstance() {
    if(!self::$instance instanceof self) {
        self::$instance = new self;
    }
    
    return self::$instance;
  }

  public function __construct() {
    $this->val = array();
    
    $domain = '';
    if(defined('COOKIE_DOMAIN') && COOKIE_DOMAIN != '') {
      $domain = COOKIE_DOMAIN;   // in config, define domain without leading dot
    }

    $http_url = osc_is_ssl() ? "https://" : "http://";
    $web_path = ($domain == '' ? WEB_PATH : $http_url . $domain);
    $this->name = md5($web_path);
    
    $this->expires = time() + (86400 * 365 * 3); // 3 years by default

    if(isset($_COOKIE[$this->name])) {
      $tmp = explode('&', $_COOKIE[$this->name]);
      
      $vars = isset($tmp[0]) ? $tmp[0] : '';
      $vals = isset($tmp[1]) ? $tmp[1] : '';
      
      $vars = explode('._.', $vars);
      $vals = explode('._.', $vals);
    
      foreach($vars as $key => $var) {
        if($var != '' && isset($vals[$key])) {
          $this->val[$var] = $vals[$key];
          setcookie($var, $vals[$key], $this->expires, REL_WEB_URL, $domain);

        } else {
          $this->val[$var] = '';
          // setcookie($var, null, -1, REL_WEB_URL, $domain); 
          setcookie($var, '', -1, REL_WEB_URL, $domain); 
        }
      }
    }
  }

  /**
   * @param $var
   * @param $value
   */
  public function push($var, $value) {
    $this->val[$var] = $value;
    
    $domain = '';
    if(defined('COOKIE_DOMAIN') && COOKIE_DOMAIN != '') {
      $domain = COOKIE_DOMAIN;   // in config, define domain without leading dot
    }
    
    setcookie($var, $value, $this->expires, REL_WEB_URL, $domain);
  }

  /**
   * @param $var
   */
  public function pop($var) {
    unset($this->val[$var]);
    
    $domain = '';
    if(defined('COOKIE_DOMAIN') && COOKIE_DOMAIN != '') {
      $domain = COOKIE_DOMAIN;   // in config, define domain without leading dot
    }
    
    setcookie($var, '', -1, REL_WEB_URL, $domain); 
  }
    
  public function clear() {
    $this->val = array();
  }
    
  public function set() {
    $cookie_val = '';
    
    if(is_array($this->val) && count($this->val) > 0) {
      $cookie_val = '';
      $vars = $vals = array();
      
      foreach ($this->val as $key => $curr){
        if($curr !== '') {
          $vars[] = $key;
          $vals[] = $curr;
        }
      }
      
      if(count($vars) > 0 && count($vals) > 0) {
        $cookie_val = implode('._.', $vars) . '&' . implode('._.', $vals);
      }
    }

    $domain = '';
    if(defined('COOKIE_DOMAIN') && COOKIE_DOMAIN != '') {
      $domain = COOKIE_DOMAIN;   // in config, define domain without leading dot
    }
    
    setcookie($this->name, $cookie_val, $this->expires, REL_WEB_URL, $domain);
  }

  /**
   * @return int
   */
  public function num_vals() {
    return count($this->val);
  }

  /**
   * @param $str
   *
   * @return mixed|string
   */
  public function get_value($str) {
    if (isset($this->val[$str])) {
      return $this->val[$str];
    }
    
    return '';
  }

  /**
   * @param $tm in seconds
   */
  public function set_expires($tm) {
    $this->expires = time() + $tm;
  }
  

  // Save user urls history
  public function _setRefererHistory($value = null) {
    $ref_hist = (array)$this->_getRefererHistory();

    // First check for true referer
    if(Params::existServerParam('HTTP_REFERER')){
      if(filter_var(Params::getServerParam('HTTP_REFERER', false, false), FILTER_VALIDATE_URL)) {
        if(Params::getServerParam('HTTP_REFERER', false, false) != '') {
          array_unshift($ref_hist, Params::getServerParam('HTTP_REFERER', false, false));
          $ref_hist = array_slice(array_filter(array_unique($ref_hist)), 0, 6);
        }
      }
    }

    // Now do current value/url
    $value = ($value === null ? osc_get_current_url() : $value);

    if($value != '' && stripos($value, osc_base_url()) !== false) {
      array_unshift($ref_hist, $value);                                  // Add latest page at first array index position
      $ref_hist = array_slice(array_filter(array_unique($ref_hist)), 0, 6);     // Keep last XY urls
    }
    
    $this->push('osc_http_referer_history', json_encode($ref_hist));
  }
  
  // Get last XY referers history
  public function _getRefererHistory() {
    $ref_hist = $this->get_value('osc_http_referer_history');
    $ref_hist = $ref_hist != '' ? @json_decode($ref_hist, true) : array();
    
    return (array)$ref_hist;
  }
  

  // From referer history, get last valid (that does not match to login/registration/redirect page
  public function _getTrueReferer() {
    $hist = (array)$this->_getRefererHistory();
    $ref = '';

    if(is_array($hist) && count($hist) > 0) {
      foreach($hist as $h) {
        $current = osc_get_current_url();
        
        if(OC_ADMIN === true || stripos(osc_get_current_url(), OC_ADMIN_FOLDER)) {
          $is_backoffice = true;
        } else {
          $is_backoffice = false;
        }

        $uri = str_replace(osc_base_url(), '', $h);
        
        // Check if it does not refer 3rd party url
        if(stripos($h, osc_base_url()) === false) {
          continue;
        }
        
        // For front - get front url, for oc-admin - get oc-admin url
        if($is_backoffice === true && stripos($h, osc_admin_base_url()) === false || $is_backoffice === false && stripos($h, osc_admin_base_url()) !== false) {
          continue;
        }

        // Check if it's standard page
        if(in_array($h, array(osc_search_url(), osc_contact_url(), osc_item_post_url(), osc_user_dashboard_url(), osc_user_items_url(), osc_user_profile_url()))) {
          return $h;
        }
        
        // Check if it's wrong page. Do not do home page too, it is too generic.
        if(in_array($h, array(osc_base_url(), osc_base_url(true), osc_base_url(true, true), osc_base_url(false, true), osc_admin_base_url(), osc_admin_base_url(true), osc_admin_base_url(true) . '&page=login', osc_user_logout_url(), osc_user_login_url(), osc_register_account_url()))) {
          continue;
        }
        
        // Check if it contains blocked word
        $blocked_words = array('login','logout','register',osc_get_preference('rewrite_user_login'),osc_get_preference('rewrite_user_logout'),osc_get_preference('rewrite_user_register'));
        $stop = false;
        
        foreach($blocked_words as $bword) {
          if(trim((string)$bword) != '' && stripos($uri, $bword) !== false) {
            $stop = true;
            break;
          }
        }
        
        if($stop === true) {
          continue;
        }
        
        return $h;
      }
    }

    return false;
  }
}

/* file end: ./oc-includes/osclass/core/Cookie.php */
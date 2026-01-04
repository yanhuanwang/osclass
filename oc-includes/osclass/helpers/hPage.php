<?php
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


/**
* Helper Pages
* @package Osclass
* @subpackage Helpers
* @author Osclass
*/


/**
 * Gets page array from view and cache it if not exists
 *
 * @return array
 */
function osc_get_page_row($id, $cache = true) {
  if($id <= 0) {
    return false;
  }

  if($cache === true && View::newInstance()->_exists('page_' . $id)) {
    return View::newInstance()->_get('page_' . $id);
  }

  // If there is more categories in DB, it's not effective way
  $pages = osc_get_pages_all(false, false, true);
  
  // Search in session array with flat categories
  if(is_array($pages) && isset($pages[$id])) {
    View::newInstance()->_exportVariableToView('currency_' . $id, $pages[$code]);
    return $pages[$id];
  }
  
  $page = Page::newInstance()->findByPrimaryKey((int)$id);
  View::newInstance()->_exportVariableToView('page_' . $id, $page);
  
  return $page;
}


/**
 * Gets current page object
 *
 * @return array
 */
function osc_static_page() {
  if (View::newInstance()->_exists('pages')) {  
    $page = View::newInstance()->_current('pages');
  } else if (View::newInstance()->_exists('page')) {  
    $page = View::newInstance()->_get('page');  
  } else {  
    $page = null;  
  }

  if (!View::newInstance()->_exists('page_meta')) {
    View::newInstance()->_exportVariableToView('page_meta', json_decode(isset($page['s_meta']) ? $page['s_meta'] : '', true));
  }

  return $page;
}

/**
 * Gets current page field
 *
 * @param string $field
 * @param string $locale
 * @return string
 */
function osc_static_page_field($field, $locale = '') {
  return osc_field(osc_static_page(), $field, $locale);
}

/**
 * Gets current page title
 *
 * @param string $locale
 * @return string
 */
function osc_static_page_title($locale = '') {
  if ($locale == '') {
    $locale = osc_current_user_locale();
  }
  
  return osc_static_page_field('s_title' , $locale);
}

/**
 * Gets current page text
 *
 * @param string $locale
 * @return string
 */
function osc_static_page_text($locale = '') {
  if ($locale == '') {
    $locale = osc_current_user_locale();
  }
  
  return osc_static_page_field('s_text' , $locale);
}

/**
 * Gets current page ID
 *
 * @return string
 */
function osc_static_page_id() {
  return osc_static_page_field('pk_i_id');
}

/**
 * Get page order
 *
 * @return int
 */
function osc_static_page_order() {
  return (int)osc_static_page_field('i_order');
}

/**
 * Get page add to footer
 *
 * @return boolean
 */
function osc_static_page_footer_link() {
  return (boolean)osc_static_page_field('b_link');
}

/**
 * Get page index or no-index
 *
 * @return boolean
 */
function osc_static_page_indexable() {
  return (boolean)osc_static_page_field('b_index');
}

/**
 * Gets current page modification date
 *
 * @return string
 */
function osc_static_page_mod_date() {
  return osc_static_page_field('dt_mod_date');
}

/**
 * Gets current page publish date
 *
 * @return string
 */
function osc_static_page_pub_date() {
  return osc_static_page_field('dt_pub_date');
}

/**
 * Gets current page slug or internal name
 *
 * @return string
 */
function osc_static_page_slug() {
  return osc_static_page_field('s_internal_name');
}

/**
 * Gets static page visibility
 *
 * @return integer
 */
function osc_static_page_visibility() {
  return osc_static_page_field('i_visibility');
}


/**
 * Gets static page visibility options
 *
 * @return string
 */
function osc_static_page_visibility_options() {
  $data = array(
    0 => __('Anyone'),
    1 => __('Logged-in users'),
    2 => __('Personal users'),
    3 => __('Company users'),
    4 => __('Admins'),
    99 => __('Hidden')
  );
  
  return osc_apply_filter('osc_static_page_visibility_options', $data);
}

/**
 * Gets static page visibility name
 *
 * @return string
 */
function osc_static_page_visibility_name($visibility_id) {
  $options = osc_static_page_visibility_options();
  
  if(isset($options[$visibility_id])) {
    return $options[$visibility_id];
  }
  
  return __('Unknown');
}


/**
 * Gets current page meta information
 *
 * @param null $field
 *
 * @return string
 */
function osc_static_page_meta($field = null) {
  if (!View::newInstance()->_exists('page_meta')) {
    $meta = json_decode(osc_static_page_field('s_meta'), true);
  } else {
    $meta = View::newInstance()->_get('page_meta');
  }
  
  if ($field == null) {
    $meta = (isset($meta[$field]) && !empty($meta[$field])) ? $meta[$field] : '';
  }
  
  return $meta;
}


/**
 * Gets current page url
 *
 * @param string $locale
 *
 * @return string
 * @throws \Exception
 */
function osc_static_page_url($locale = '') {
  if (osc_rewrite_enabled()) {
    $sanitized_categories = array();
    $cat = Category::newInstance()->hierarchy(osc_item_category_id());
    
    for($i = count($cat); $i > 0; $i--) {
      $sanitized_categories[] = $cat[$i - 1]['s_slug'];
    }
    
    $url = str_replace(
      array('{PAGE_ID}', '{PAGE_TITLE}'), 
      array(osc_static_page_id(), osc_static_page_title()), 
      str_replace('{PAGE_SLUG}', urlencode(osc_static_page_slug()), osc_get_preference('rewrite_page_url'))
    );
    
    if($locale!='') {
      $path = osc_base_url().$locale . '/' . $url;
    } else {
      $path = osc_base_url(false, true).$url;
    }
    
  } else {
    if($locale!='') {
      $path = osc_base_url(true) . '?page=page&id=' . osc_static_page_id() . '&lang=' . $locale;
    } else {
      $path = osc_base_url(true) . '?page=page&id=' . osc_static_page_id();
    }
  }
  
  return $path;
}


/**
 * Gets the specified static page by internal name.
 *
 * @param string $internal_name
 * @param string $locale
 *
 * @return void
 */
function osc_get_static_page($internal_name, $locale = '') {
  if($locale == '') {
    $locale = osc_current_user_locale();
  }
  
  $page = Page::newInstance()->findByInternalName($internal_name, $locale);
  View::newInstance()->_exportVariableToView('page_meta', json_decode(isset($page['s_meta']) ? $page['s_meta'] : '', true));
  
  return View::newInstance()->_exportVariableToView('page', $page);
}

/**
 * Gets the total of static pages. If static pages are not loaded, this function will load them.
 *
 * @return int
 */
function osc_count_static_pages() {
  osc_get_pages();
  
  return View::newInstance()->_count('pages');
}

/**
 * Let you know if there are more static pages in the list. If static pages are not loaded,
 * this function will load them.
 *
 * @return boolean
 */
function osc_has_static_pages() {
  osc_get_pages();
  
  $page = View::newInstance()->_next('pages');
  View::newInstance()->_exportVariableToView('page_meta', json_decode(isset($page['s_meta']) ? $page['s_meta'] : '', true));
  
  return $page;
}


/**
 * Move the iterator to the first position of the pages array
 * It reset the osc_has_page function so you could have several loops
 * on the same page
 *
 * @return void
 */
function osc_reset_static_pages() {
  return View::newInstance()->_erase('pages');
}



/**
 * Gets list of pages for footer section
 *
 * @return string
 */
function osc_get_pages($link = true) {
  if(!View::newInstance()->_exists('pages')) {
    $pages = osc_get_pages_all($link);
    View::newInstance()->_exportVariableToView('pages', $pages);
    
    return $pages;
  }
  
  return View::newInstance()->_get('pages');
}


/**
 * Gets list of pages
 *
 * @return string
 */
function osc_get_pages_all($link = null, $only_visible = true, $by_pk = false, $indelible = false) {
  $key = 'pages_all_' . (string)$link . (string)$only_visible . (string)$by_pk . (string)$indelible;

  if(!View::newInstance()->_exists($key)) {
    $pages = Page::newInstance()->listAll($indelible);
    $output = array();
    
    if(is_array($pages) && count($pages) > 0) {
      foreach($pages as $page) {
        if($indelible === null || $indelible === true && $page['b_indelible'] == 1 || $indelible === false && $page['b_indelible'] == 0) {
          if($link === null || $link === true && $page['b_link'] == 1 || $link === false && $page['b_link'] == 0) {
            if($only_visible === null || $only_visible === true && osc_check_static_page_user_visibility($page, osc_logged_user()) === true) {
              if($by_pk) {
                $output[$page['pk_i_id']] = $page;
              } else {
                $output[] = $page;
              }
            }
          }
        }
      }
    }
    
    View::newInstance()->_exportVariableToView($key, $output);
    
    return $output;
  }
  
  return View::newInstance()->_get($key);
}

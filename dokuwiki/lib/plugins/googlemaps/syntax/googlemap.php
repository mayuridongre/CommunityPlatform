<?php
/**
 * Plugin Google Maps: Allow Display of a Google Map in a wiki page.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

// ---------- [ Settings ] -----------------------------------------

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_googlemaps_googlemap extends DokuWiki_Syntax_Plugin {

    var $dflt = array(
      'type' => 'map',
      'width' => '',
      'height' => '',
      'lat'  => -4.25,
      'lon' => 55.833,
      'zoom' => 8,
      'controls' => 'on',
      'kml' => 'off',
    );

    function getInfo(){
      return array(
        'author' => 'Christopher Smith',
        'email'  => 'chris@jalakai.co.uk',
        'date'   => '2008-11-11',
        'name'   => 'Google Maps Plugin',
        'desc'   => 'Add maps to your wiki
                     Syntax: <googlemap params>overlaypoints</googlemap>',
        'url'    => 'http://www.dokuwiki.org/plugin:google_maps',
      );
    }

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 900; } 

    function connectTo($mode) { 
        $this->Lexer->addSpecialPattern('<googlemap ?[^>\n]*>.*?</googlemap>',$mode,'plugin_googlemaps_googlemap'); 
    }

    function handle($match, $state, $pos, &$handler){

      // break matched cdata into its components
      list($str_params,$str_points) = explode('>',substr($match,10,-12),2);

      $gmap = $this->_extract_params($str_params);
      $overlay = $this->_extract_points($str_points);

      // determine width and height (inline styles) for the map image
      if ($gmap['width'] || $gmap['height']) {
        $style = $gmap['width'] ? 'width: '.$gmap['width'].";" : "";
        $style .= $gmap['height'] ? 'height: '.$gmap['height'].";" : "";
        $style = "style='$style'";
      } else {
        $style = '';
      }      

      // unset gmap values for width and height - they don't go into javascript
      unset($gmap['width'],$gmap['height']);

      // create a javascript parameter string for the map
      $param = '';
      foreach ($gmap as $key => $val) {
          $param .= is_numeric($val) ? " $key : $val," : "$key : '".hsc($val)."',";
      }

      if (!empty($param)) $param = substr($param,0,-1);

      // create a javascript serialisation of the point data
      $points = '';
      if (!empty($overlay)) {
        foreach ($overlay as $data) {
          list($lat,$lon,$text) = $data;
          $points .= ",{lat:$lat,lon:$lon,txt:'$text'}";
        }
        $points = ", overlay : [ ".substr($points,1)." ]";
      }

      $js = "googlemap[googlemap.length] = {".$param.$points." };";

      return array($style, $js);
    }

    function render($mode, &$renderer, $data) {

      static $initialised = false;    // set to true after script initialisation

      if ($mode == 'xhtml') {
        list($style, $param) = $data;

        $script = '';

        if (!$initialised) {
          $initialised = true;
          $script = $this->getConf('script').$this->getConf('key');
          $script = '<script type="text/javascript" src="'.$script.'"></script>';
        }
      
        $renderer->doc .= "
<div class='googlemap' $style>
$script
<script type='text/javascript'>
$param
</script>
</div>";
      }

      return false;
    } 

    /**
     * extract parameters for the googlemap from the parameter string
     *
     * @param   string    $str_params   string of key="value" pairs
     * @return  array                   associative array of parameters key=>value
     */
    function _extract_params($str_params) {

      $param = array();
      preg_match_all('/(\w*)="(.*?)"/us',$str_params,$param,PREG_SET_ORDER);
      
      // parse match for instructions, break into key value pairs      
      $gmap = $this->dflt;
      foreach($param as $kvpair) {
        list($match,$key,$val) = $kvpair;
        $key = strtolower($key);
        if (isset($gmap[$key])) $gmap[$key] = strtolower($val);        
      }

      return $gmap;
    }

    /**
     * extract overlay points for the googlemap from the wiki syntax data
     *
     * @param   string    $str_points   multi-line string of lat,lon,text triplets
     * @return  array                   multi-dimensional array of lat,lon,text triplets
     */
    function _extract_points($str_points) {

      $point = array();
      preg_match_all('/^(.*?),(.*?),(.*)$/um',$str_points,$point,PREG_SET_ORDER);

      $overlay = array();
      foreach ($point as $pt) {
        list($match,$lat,$lon,$text) = $pt;

        $lat = is_numeric($lat) ? $lat : 0;
        $lon = is_numeric($lon) ? $lon : 0;
        $text = addslashes(str_replace("\n","",p_render("xhtml",p_get_instructions($text),$info)));

        $overlay[] = array($lat,$lon,$text);
      }

      return $overlay;
    }

}
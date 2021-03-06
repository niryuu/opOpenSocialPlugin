<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opOpenSocialToolKit
 *
 * @package    opOpenSocialPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@tejimaya.net>
 */
class opOpenSocialToolKit
{
  static protected function arrayToObject($array)
  {
    foreach ($array as &$a)
    {
      if (is_array($a))
      {
        $a = self::arrayToObject($a);
      }
    }

    return (object)$array;
  }

  /**
   * fetch a OpenSocial application metadata
   *
   * @param string $url
   * @param string $culture
   */
  static public function fetchGadgetMetadata($url, $culture)
  {
    $cul = explode('_', $culture);

    $_GET['nocache'] = 1;
    $context = new MetadataGadgetContext(self::arrayToObject(array(
      'country'   => isset($cul[1]) ? $cul[1] : 'ALL',
      'language'  => $cul[0],
      'view'      => 'default',
      'container' => 'openpne',
    )), $url);
    $gadgetServer = new GadgetFactory($context, null);
    $gadgets = $gadgetServer->createGadget();
    return $gadgets;
  }

 /**
  * Check enable home gadget
  *
  * @return boolean
  */
  static public function isEnableHomeGadget()
  {
    $homeGadgets = Doctrine::getTable('Gadget')->retrieveGadgetsByTypesName('gadget');
    foreach ($homeGadgets as $gadgets)
    {
      if ($gadgets)
      {
        foreach ($gadgets as $gadget)
        {
          if (($gadget instanceof Gadget) && $gadget->getName() == 'applicationBoxes')
          {
            return true;
          }
        }
      }
    }
    return false;
  }

 /**
  * Check enable profile gadget
  *
  * @return boolean
  */
  static public function isEnableProfileGadget()
  {
    $profileGadgets = Doctrine::getTable('Gadget')->retrieveGadgetsByTypesName('profile');
    foreach ($profileGadgets as $gadgets)
    {
      if ($gadgets)
      {
        foreach ($gadgets as $gadget)
        {
          if (($gadget instanceof Gadget) && $gadget->getName() == 'applicationBoxes')
          {
            return true;
          }
        }
      }
    }
    return false;
  }

 /**
  * get consumer key for RSA-SHA1
  *
  * @return OAuthConsumer
  */
  static public function getOAuthConsumerKey()
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('opUtil'));
    $baseUrl = sfConfig::get('op_base_url');
    if ('/' === substr($baseUrl, -1))
    {
      $baseUrl = substr($baseUrl, 0, strlen($baseUrl) - 1);
    }
    return $baseUrl.app_url_for('pc_frontend', '@opensocial_certificates');
}

 /**
  * get http option for Zend_Http_Client
  *
  * @return array
  */
  static public function getHttpOptions()
  {
    $proxyUrl = Shindig_Config::get('proxy');
    $httpOptions = array();
    if (!empty($proxyUrl))
    {
      $httpOptions['adapter'] = 'Zend_Http_Client_Adapter_Proxy';
      $proxy = parse_url($proxyUrl);
      if (isset($proxy['host']))
      {
        $httpOptions['proxy_host'] = $proxy['host'];
      }

      if (isset($proxy['port']))
      {
        $httpOptions['proxy_port'] = $proxy['port'];
      }

      if (isset($proxy['user']))
      {
        $httpOptions['proxy_user'] = $proxy['user'];
      }

      if (isset($proxy['pass']))
      {
        $httpOptions['proxy_pass'] = $proxy['pass'];
      }
    }
    $httpOptions['timeout'] = Shindig_Config::get('curl_connection_timeout');
    return $httpOptions;
  }

 /**
  * getProxyHeaders
  *
  * @param sfWebRequest $request
  * @param boolean      $isStripUid
  * @return array
  */
  static public function getProxyHeaders($request, $isStripUid = true)
  {
    $results = array();
    if ($request->getHttpHeader('User-Agent'))
    {
      $userAgent = $request->getHttpHeader('User-Agent');
      if ($isStripUid)
      {
        if (preg_match('#^(DoCoMo/1\.0.*)/(ser.*)$#', $userAgent, $match))
        {
          $userAgent = $match[1];
        }
        elseif (preg_match('#^(DoCoMo/2\.0) (.*)\((.*);(ser.*)\)$#', $userAgent, $match))
        {
          $userAgent = $match[1].' '.$match[2].'('.$match[3].')';
        }
        elseif (preg_match('#^((SoftBank|Vodafone|J-PHONE)/.*/.*)(/SN\S*) (.*)$#', $userAgent, $match))
        {
          $userAgent = $match[1].' '.$match[4];
        }
      }
      $results['User-Agent'] = $userAgent;
    }

    if (!$isStripUid)
    {
      $headerNames = array('X-DCMGUID', 'X-UP-SUBNO','X-JPHONE-UID');
      foreach ($headerNames as $name)
      {
        if ($request->getHttpHeader($name))
        {
          $results[$name] = $request->getHttpHeader($name);
        }
      }
    }

    $pathArray = $request->getPathInfoArray();

    foreach ($pathArray as $name => $value)
    {
      if (preg_match('/^HTTP_(X_(UP|JPHONE)_.*)$/', $name, $match))
      {
        $name = strtr($match[1], '_', '-');
        if ($name !== 'X-JPHONE-UID' && $name !== 'X-UP-SUBNO')
        {
          $results[$name] = $value;
        }
      }
    }

    $name = 'X-S-DISPLAY-INFO';
    if ($request->getHttpHeader($name))
    {
      $results[$name] = $request->getHttpHeader($name);
    }

    return $results;
  }

 /**
  * rewriteBodyForMobile
  *
  * @param sfAction $action
  * @param string   $body
  * @return string
  */
  static public function rewriteBodyForMobile(sfAction $action, $body)
  {
    $patterns = array();
    $replacements = array();

    $patterns[] = "/<\?xml(.*)encoding=(?:\"|').*(?:\"|')/iU";
    $replacements[] = '<?xml${1}encoding="shift-jis"';

    $patterns[] = "/<meta(.*)content=\"(.*);\s*charset=(.*)(;.*)?\"(.*)>/iU";
    $replacements[] = '<meta${1}content="${2}; charset=shift-jis${4}"${5}>';

    $partials = array(
      $action->getPartial('global/partsPageTitle', array('title' => $action->application->getTitle())),
      $action->getPartial('application/renderFooter', array('application' => $action->application))
    );

    if ($action->getRequest()->getMobile()->isDoCoMo() && opConfig::get('font_size'))
    {
      $pattern_start_tag = '/(<td.*?>)/';
      $replacement_start_tag = '$1<font size="2">';
      $pattern_end_tag = '</td>';
      $replacement_end_tag = '</font></td>';
      $partials = preg_replace($pattern_start_tag, $replacement_start_tag, $partials);
      $partials = str_replace($pattern_end_tag, $replacement_end_tag, $partials);
      foreach ($partials as &$partial)
      {
        $partial = '<font size="2">'.$partial.'</font>';
      }
    }

    $patterns[] = "/<body.*>/iU";
    $replacements[] = '${0}'.$partials[0];

    $patterns[] = "/<\/body>/i";
    $replacements[] = $partials[1].'${0}';

    $inviteUrl = $action->getController()->genUrl('@application_invite?id='.$action->memberApplication->getId());
    $patterns[] = "/<a(.*)href=(?:'|\")(invite:friends)(.*)(?:'|\")(.*)>/iU";
    $replacements[] = '<a${1}href="'.$inviteUrl.'${3}"${4}>';

    return mb_convert_encoding(preg_replace($patterns, $replacements, $body), 'SJIS-win', 'UTF-8');
  }
}

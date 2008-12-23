<?php

/**
 * application components
 *
 * @package    OpenPNE
 * @subpackage saOpenSocialPlugin
 * @author     ShogoKawahara <kawahara@tejimaya.net>
 */
class applicationComponents extends sfComponents
{
  public function executeGadget()
  {
    $member_app = $this->member_app;
    $mod_id     = $member_app->getId();
    $this->mid  = $mod_id;
    $app = $member_app->getApplication();
    $this->title = $app->getTitle();
    $owner_id = $member_app->getMemberId();
    $app_id   = $app->getId();
    $url      = $app->getUrl();
    $culture = $this->getUser()->getCulture();
    $culture = split("_",$culture);
    $this->aid       = $app_id;
    $this->height    = $app->getHeight() ? $app->getHeight() : 200;
    $this->scrolling = $app->getScrolling();

    $viewer_id = $this->getUser()->getMemberId();

    $this->isViewer = false;
    if ($owner_id == $viewer_id)
    {
      $this->isViewer = true;
    }

    $securityToken = BasicSecurityToken::createFromValues(
      $owner_id,  // owner
      $viewer_id, // viewer
      $app_id,    // app id
      'default',  // domain key
      urlencode($url), // app url
      $mod_id    // mod id
    );

    $getParams = array(
      'synd'      => 'default',
      'container' => 'default',
      'owner'     => $owner_id,
      'viewer'    => $viewer_id,
      'aid'       => $app_id,
      'mid'       => $mod_id,
      'country'   => isset($culture[1]) ? $culture[1] : 'US',
      'lang'      => $culture[0],
      'view'      => $this->view,
      'parent'    => $this->getRequest()->getUri(),
      'st'        => base64_encode($securityToken->toSerialForm()),
      'url'       => $url,
    );
    $criteria = new Criteria();
    $criteria->add(ApplicationSettingPeer::MEMBER_APPLICATION_ID, $mod_id);
    $app_settings = ApplicationSettingPeer::doSelect($criteria);
    $userpref_param_prefix = Config::get('userpref_param_prefix','up_');
    foreach ($app_settings as $app_setting)
    {
      $getParams[$userpref_param_prefix.$app_setting->getName()] = $app_setting->getValue();
    }
    $this->iframe_url = sfContext::getInstance()->getController()->genUrl('gadgets/ifr').'?'.http_build_query($getParams).'#rpctoken='.rand(0,getrandmax());
  }

  public function executeHomeApplication()
  {
  }
}
